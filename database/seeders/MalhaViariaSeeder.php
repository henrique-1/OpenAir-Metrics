<?php

namespace Database\Seeders;

use App\Models\MalhaViaria;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class MalhaViariaSeeder extends Seeder
{
    /**
     * Executa a importação completa da malha viária do Brasil (.osm.pbf) diretamente para o MariaDB via ogr2ogr.
     */
    public function run(): void
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        $storageDir = storage_path('app/osm');
        File::ensureDirectoryExists($storageDir);

        $pbfFile = $storageDir.'/brazil-latest.osm.pbf';
        $pbfUrl = env('OSM_PBF_URL', 'https://download.geofabrik.de/south-america/brazil-latest.osm.pbf');

        $this->command?->info('===================================================================');
        $this->command?->info('  Carga da Malha Viária do Brasil (OpenStreetMap -> MariaDB)');
        $this->command?->info('===================================================================');

        // 1. Download do arquivo .osm.pbf via Process::run() (wget com fallback para curl)
        if (! File::exists($pbfFile) || filesize($pbfFile) < 10000000) {
            $this->command?->info("Baixando malha viária de {$pbfUrl}...");
            $this->command?->info("Arquivo de destino: {$pbfFile}");

            $downloadStartTime = microtime(true);
            try {
                $downloadProcess = Process::timeout(0)->run("wget -c -O \"{$pbfFile}\" \"{$pbfUrl}\"");

                if (! $downloadProcess->successful() || ! File::exists($pbfFile) || filesize($pbfFile) === 0) {
                    $downloadProcess = Process::timeout(0)->run("curl -L -C - -o \"{$pbfFile}\" \"{$pbfUrl}\"");
                }

                if ($downloadProcess->successful() && File::exists($pbfFile) && filesize($pbfFile) > 0) {
                    $tempoDownload = round(microtime(true) - $downloadStartTime, 1);
                    $tamanhoMb = round(filesize($pbfFile) / (1024 * 1024), 2);
                    $this->command?->info("Download concluído em {$tempoDownload}s! Tamanho: {$tamanhoMb} MB.");
                } else {
                    $this->command?->warn('Não foi possível concluir o download completo via wget/curl.');
                    Log::warning('Download .osm.pbf falhou: '.$downloadProcess->errorOutput());
                }
            } catch (\Throwable $e) {
                $this->command?->warn('Exceção ao baixar arquivo .osm.pbf: '.$e->getMessage());
                Log::warning('Exceção ao baixar arquivo .osm.pbf: '.$e->getMessage());
            }
        } else {
            $tamanhoMb = round(filesize($pbfFile) / (1024 * 1024), 2);
            $this->command?->info("Arquivo .osm.pbf presente em disco: {$tamanhoMb} MB.");
        }

        // 2. Ingestão no MariaDB via ogr2ogr com monitoramento de Progresso e ETA em tempo real
        $importedViaOgr = false;

        if (! app()->environment('testing') && File::exists($pbfFile) && filesize($pbfFile) > 0) {
            $this->command?->info('Iniciando importação de vias para MariaDB via ogr2ogr...');

            $db = config('database.connections.mysql.database');
            $user = config('database.connections.mysql.username');
            $pass = config('database.connections.mysql.password');
            $host = config('database.connections.mysql.host');
            $port = config('database.connections.mysql.port', 3306);

            // No driver GDAL MySQL, o nome do banco deve vir imediatamente após "MYSQL:" (sem "database=")
            $mysqlConn = sprintf('MYSQL:%s,user=%s,password=%s,host=%s,port=%s', $db, $user, $pass, $host, $port);
            $sqlQuery = "SELECT name AS logradouro, highway AS tipo_via, geometry AS geometria FROM lines WHERE highway IN ('residential', 'tertiary', 'secondary', 'primary', 'primary_link', 'secondary_link', 'tertiary_link')";

            $ogrCommand = sprintf(
                'ogr2ogr -f "MySQL" "%s" "%s" -dialect SQLite -sql "%s" -nln malha_viaria -overwrite -lco GEOMETRY_NAME=geometria -lco FID=id -lco ENGINE=InnoDB -lco SPATIAL_INDEX=NO -gt 65536 -progress --config OSM_USE_CUSTOM_INDEXING YES',
                $mysqlConn,
                $pbfFile,
                $sqlQuery
            );

            $startTime = microtime(true);
            $lastPercentage = 0;

            try {
                $process = Process::timeout(0)->start($ogrCommand);

                while ($process->running()) {
                    $output = $process->latestOutput();
                    if (! empty($output)) {
                        if (preg_match_all('/(\d+)\s*\.{3}/', $output, $matches)) {
                            $latestPct = (int) end($matches[1]);
                            if ($latestPct > $lastPercentage && $latestPct <= 100) {
                                $lastPercentage = $latestPct;
                                $elapsed = microtime(true) - $startTime;
                                $etaSeconds = ($latestPct > 0) ? ($elapsed / $latestPct) * (100 - $latestPct) : 0;

                                $elapsedFormatted = gmdate('H:i:s', (int) $elapsed);
                                $etaFormatted = gmdate('H:i:s', (int) $etaSeconds);

                                $this->command?->info(sprintf(
                                    '  Progresso: [%-20s] %3d%% | Decorrido: %s | ETA: %s',
                                    str_repeat('=', (int) ($latestPct / 5)).(int) ($latestPct < 100 ? '>' : ''),
                                    $latestPct,
                                    $elapsedFormatted,
                                    $etaFormatted
                                ));
                            }
                        }
                    }
                    usleep(500000); // 0.5s
                }

                $result = $process->wait();
                if ($result->successful()) {
                    $importedViaOgr = true;
                    $totalElapsed = gmdate('H:i:s', (int) (microtime(true) - $startTime));
                    $this->command?->info("Importação via ogr2ogr concluída com sucesso! Tempo total: {$totalElapsed}");

                    $this->command?->info('Compilando índice espacial (SPATIAL INDEX) na tabela malha_viaria...');
                    DB::statement('ALTER TABLE malha_viaria ADD SPATIAL INDEX(geometria)');
                    $this->command?->info('Índice espacial compilado com sucesso!');
                } else {
                    $this->command?->warn('ogr2ogr retornou erro: '.$result->errorOutput());
                    Log::warning('ogr2ogr MariaDB falhou: '.$result->errorOutput());
                }
            } catch (\Throwable $e) {
                $this->command?->warn('Exceção ao executar ogr2ogr no MariaDB: '.$e->getMessage());
                Log::warning('Exceção ao executar ogr2ogr no MariaDB: '.$e->getMessage());
            }
        }

        // 3. Fallback de vias de referência para testes automatizados e ambientes sem GDAL
        if (! $importedViaOgr && MalhaViaria::count() === 0) {
            $this->command?->info('Inserindo vias estruturadas de teste no banco de dados...');
            $this->semearViasExemplo();
            $this->command?->info('Vias de referência inseridas com sucesso.');
        }

        $totalVias = MalhaViaria::count();
        $this->command?->info("Carga concluída! Total de vias na tabela malha_viaria: {$totalVias}");
    }

    /**
     * Semeia vias de exemplo com geometrias reais para testes locais e unitários.
     */
    protected function semearViasExemplo(): void
    {
        $vias = [
            [
                'logradouro' => 'Rua Saldanha Marinho',
                'tipo_via' => 'residential',
                'wkt' => 'LINESTRING(-46.7970 -21.9840, -46.7950 -21.9845, -46.7930 -21.9850, -46.7910 -21.9855)',
            ],
            [
                'logradouro' => 'Avenida Dona Gertrudes',
                'tipo_via' => 'primary',
                'wkt' => 'LINESTRING(-46.7960 -21.9860, -46.7947 -21.9847, -46.7935 -21.9830, -46.7920 -21.9815)',
            ],
            [
                'logradouro' => 'Rua Marechal Deodoro',
                'tipo_via' => 'secondary',
                'wkt' => 'LINESTRING(-46.7980 -21.9830, -46.7947 -21.9847, -46.7915 -21.9860)',
            ],
            [
                'logradouro' => 'Rua Ademar de Barros',
                'tipo_via' => 'tertiary',
                'wkt' => 'LINESTRING(-46.7947 -21.9847, -46.7940 -21.9870, -46.7930 -21.9890)',
            ],
            [
                'logradouro' => 'Avenida Paulista',
                'tipo_via' => 'primary',
                'wkt' => 'LINESTRING(-46.6650 -23.5550, -46.6550 -23.5610, -46.6450 -23.5680)',
            ],
            [
                'logradouro' => 'Rua Augusta',
                'tipo_via' => 'secondary',
                'wkt' => 'LINESTRING(-46.6530 -23.5500, -46.6580 -23.5580, -46.6620 -23.5650)',
            ],
        ];

        $isSqlite = DB::getDriverName() === 'sqlite';

        foreach ($vias as $via) {
            MalhaViaria::create([
                'logradouro' => $via['logradouro'],
                'tipo_via' => $via['tipo_via'],
                'geometria' => $isSqlite
                    ? $via['wkt']
                    : DB::raw(sprintf("ST_GeomFromText('%s', 4326)", $via['wkt'])),
            ]);
        }
    }
}
