<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img src="https://i.imgur.com/xjCK3np.png" class="logo" alt="QuestApp Logo">
@else
{{ $slot }}
@endif
</a>
</td>
</tr>
