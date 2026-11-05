<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ config('app.name') }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
<p style="margin: 0; color: #738a9c; font-size: 12px; line-height: 1.5; text-align: center;">
    <strong>OpenAir Metrics</strong> &bull; Sistema Integrado de Monitoramento da Qualidade do Ar<br>
    &copy; {{ date('Y') }} Todos os direitos reservados.
</p>
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
