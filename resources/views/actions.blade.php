<a
    class=""
    type="button"
    id="dropdownMenuButton"
    data-bs-toggle="dropdown"
    aria-expanded="false"
>
    <i class="ti ti-menu-2"></i>
</a>
<ul
    class="dropdown-menu"
    aria-labelledby="dropdownMenuButton"
>
    @foreach($actions as $action)
        <li>{!! $action->getLink($item, $list, "", "dropdown-item" ) !!}</li>
    @endforeach
</ul>
