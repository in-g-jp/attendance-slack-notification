@if(!empty($parsedMembers))@if($mentionLine !== ''){!! $mentionLine !!}

@endif
*{{ $dateLine }}*

@foreach($roles as $role)
@if(isset($groupedMembers[$role]))
{{ $role }}
```
@foreach($groupedMembers[$role] as $member)
{{ $member['name'] }} @if($member['workTime']){{ $member['workTime'] }} @endif{{ $member['status'] }}
@endforeach
```
@endif
@endforeach
@endif
