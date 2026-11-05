@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
    <table cellpadding="0" cellspacing="0" border="0" align="center" style="margin: 0 auto;">
        <tr>
            <td align="center" style="vertical-align: middle;">
                <img src="{{ rtrim(config('app.url'), '/') }}/images/3.png" class="logo" alt="{{ config('app.name', 'OpenAir Metrics') }}" style="height: 52px; max-height: 52px; width: auto; display: block; border: 0;">
            </td>
        </tr>
    </table>
</a>
</td>
</tr>
