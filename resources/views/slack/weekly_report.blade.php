@if($mentionLine !== ''){!! $mentionLine !!}

@endif
来週（{{ $start->format('n/j') }}〜）の予定一覧
@foreach($roles as $role)
@if(isset($groupedMembers[$role]))
*{{ $role }}*
@foreach($groupedMembers[$role] as $member)
・{{ $member['name'] }}
```
@foreach($member['schedules'] as $schedule)
{{ $schedule['text'] }}
@endforeach
```
@endforeach
@endif
@endforeach
