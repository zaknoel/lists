@php use Zak\Lists\Fields\Location; @endphp
@php /**@var Location $field*/ @endphp
<input
    type="text"
    @class(["form-control", 'is-invalid'=>$errors->has($field->attribute)])
    name="{{$field->attribute}}"
    value="{{old($field->attribute)?:$field->value}}"
    @required($field->isRequired())
    id="inputFor{{$field->attribute}}"
    placeholder="{{$field->showLabel()}}">
@error($field->attribute)
<div class="invalid-feedback">
    {{$message}}
</div>
@enderror
<a href="javascript:void(0)" class="mt-2 d-block ms-1 changer" data-block=".map_{{$field->attribute}}" data-status="0" data-text="Скрыть карту">Показать карту</a>
<div class="map_{{$field->attribute}}" style="display: none">
    <div id="map_{{$field->attribute}}" style="width: 100%; height: 400px; ">

    </div>
</div>
@push("scripts")
    <script>
        $(function () {
            ymaps.ready(init_{{$field->attribute}});
        })

        function init_{{$field->attribute}}() {
            let myPlacemark,
                myMap = new ymaps.Map('map_{{$field->attribute}}', {
                    center: [{{$field->default}}],
                    zoom: 15,
                    behaviors: ['default', 'scrollZoom']
                }, {
                    searchControlProvider: 'yandex#search'
                });
            if ($('#inputFor{{$field->attribute}}').val()) {

                let c = $('#inputFor{{$field->attribute}}').val().split(",");
                c = [parseFloat(c[0]), parseFloat(c[1])];
                myPlacemark = createPlacemark(c);
                myMap.geoObjects.add(myPlacemark);
                // Слушаем событие окончания перетаскивания на метке.
                myPlacemark.events.add('dragend', function () {
                    $('#inputFor{{$field->attribute}}').val(myPlacemark.geometry.getCoordinates().join(","));
                    getAddress(myPlacemark.geometry.getCoordinates());
                });
                getAddress(c)
                myMap.setCenter([parseFloat(c[0]), parseFloat(c[1])], 17, {
                    checkZoomRange: true
                })
            }
            // Слушаем клик на карте.
            myMap.events.add('click', function (e) {
                const coords = e.get('coords');

                // Если метка уже создана – просто передвигаем ее.
                if (myPlacemark) {
                    myPlacemark.geometry.setCoordinates(coords);
                }
                // Если нет – создаем.
                else {
                    myPlacemark = createPlacemark(coords);
                    myMap.geoObjects.add(myPlacemark);
                    // Слушаем событие окончания перетаскивания на метке.
                    myPlacemark.events.add('dragend', function () {
                        $('#inputFor{{$field->attribute}}').val(myPlacemark.geometry.getCoordinates().join(","))
                        getAddress(myPlacemark.geometry.getCoordinates());
                    });
                }
                $('#inputFor{{$field->attribute}}').val(coords.join(","))
                getAddress(coords);
            });

            // Создание метки.
            function createPlacemark(coords) {
                return new ymaps.Placemark(coords, {
                    iconCaption: 'поиск...'
                }, {
                    preset: 'islands#violetDotIconWithCaption',
                    draggable: true
                });
            }

            // Определяем адрес по координатам (обратное геокодирование).
            function getAddress(coords) {
                myPlacemark.properties.set('iconCaption', 'поиск...');
                ymaps.geocode(coords).then(function (res) {
                    var firstGeoObject = res.geoObjects.get(0);

                    myPlacemark.properties
                        .set({
                            // Формируем строку с данными об объекте.
                            iconCaption: [
                                // Название населенного пункта или вышестоящее административно-территориальное образование.
                                firstGeoObject.getLocalities().length ? firstGeoObject.getLocalities() : firstGeoObject.getAdministrativeAreas(),
                                // Получаем путь до топонима, если метод вернул null, запрашиваем наименование здания.
                                firstGeoObject.getThoroughfare() || firstGeoObject.getPremise()
                            ].filter(Boolean).join(', '),
                            // В качестве контента балуна задаем строку с адресом объекта.
                            balloonContent: firstGeoObject.getAddressLine()
                        });
                });
            }
        }

    </script>
@endpush
