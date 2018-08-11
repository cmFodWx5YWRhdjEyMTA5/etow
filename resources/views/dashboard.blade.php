@extends('layouts.main')

@section('content')
<meta http-equiv="x-ua-compatible" content="IE=edge">
<div id="page-inner">
    <!-- /. ROW  -->
    <div class="row">
        <div class="col-md-4 col-sm-12 col-xs-12">
            <div class="board">
                <div class="panel panel-primary">
                    <div class="number">
                        <h3>
                            <h3>{{ $data['total_users'] }}</h3>
                            <small>Total App Users</small>
                        </h3>
                    </div>
                    <div class="icon">
                        <i class="fa fa-users fa-5x blue"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-12 col-xs-12">
            <div class="board">
                <div class="panel panel-primary">
                    <div class="number">
                        <h3>
                            <h3>{{ $data['total_drivers'] }}</h3>
                            <small>Driver And Trucks</small>
                        </h3>
                    </div>
                    <div class="icon">
                        <i class="fa fa-user fa-5x yellow"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-12 col-xs-12">
            <div class="board">
                <div class="panel panel-primary">
                    <div class="number">
                        <h3>
                            <h3>{{ $data['total_trip_completed'] }}</h3>
                            <small>Total Completed Trips</small>
                        </h3>
                    </div>
                    <div class="icon">
                        <i class="fa fa-smile-o fa-5x green"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-12 col-xs-12">
            <div class="board">
                <div class="panel panel-primary">
                    <div class="number">
                        <h3>
                            <h3>{{ $data['total_trip_rejected'] }}</h3>
                            <small>Total Rejected Trips</small>
                        </h3>
                    </div>
                    <div class="icon">
                        <i class="fa fa-frown-o fa-5x red"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-12 col-xs-12">
            <div class="board">
                <div class="panel panel-primary">
                    <div class="number">
                        <h3>
                            <h3>{{ $data['total_trip_canceled'] }}</h3>
                            <small>Total Canceled  Trips</small>
                        </h3>
                    </div>
                    <div class="icon">
                        <i class="fa fa-calendar fa-5x red"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-12 col-xs-12">
            <div class="board">
                <div class="panel panel-primary">
                    <div class="number">
                        <h3>
                            <h3>{{ $data['total_trip_ongoing'] }}</h3>
                            <small>Number Of Users on trip</small>
                        </h3>
                    </div>
                    <div class="icon">
                        <i class="fa fa-tachometer fa-5x blue"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-12 col-xs-12">
            <div class="board">
                <div class="panel panel-primary">
                    <div class="number">
                        <h3>
                            <h3>{{ $data['total_driver_free'] }}</h3>
                            <small>Current Free Drivers</small>
                        </h3>
                    </div>
                    <div class="icon">
                        <i class="fa fa-gamepad fa-5x green"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-12 col-xs-12">
            <div class="board">
                <div class="panel panel-primary">
                    <div class="number">
                        <h3>
                            <h3>{{ $data['total_driver_offline'] }}</h3>
                            <small>Current Offline Drivers</small>
                        </h3>
                    </div>
                    <div class="icon">
                        <i class="fa fa-coffee fa-5x yellow"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="board">
                <div class="panel panel-primary">
                    <div class="col-sm-12">
                        <div class="col-sm-6">
                            <h3 style="font-size:21px;">Current Vehicle On Trip</h3>
                        </div>
                        <div class="col-sm-6">
                            <a id="btnRefresh"
                               style="background:#ff0000 !important; color:#fff; padding:2px 8px 2px 8px; float:right; cursor: pointer">
                                Refresh Map</a>
                        </div>
                    </div>
                    <div class="col-sm-12" id="map">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /. PAGE INNER  -->
@stop

@push('css-stack')
<style>
    #map {
        width: 100%;
        height: 477px;
    }
</style>
@endpush

@push('js-stack')
<script>
    var database = firebase.database();
    var tripRef = database.ref('trip');
    tripRef.on("child_changed", function(snap) {
        getMarker();
    });

    $('#btnRefresh').on('click', function () {
        getMarker();
    });

    function getMarker() {
        $.ajax({
            type: "POST",
            url: '{{ route('ajax.trip.get_marker') }}',
            data:{
                "_token": "{{ csrf_token() }}"
            },
            success: initMap
        });
    }
    getMarker();

    function initMap(markers) {
        var options = {
            zoom: 12,
            center: {lat:21.017079, lng:105.783594}
        };

        var map = new google.maps.Map(document.getElementById('map'), options);
        google.maps.event.addListener(map, 'click', function () {
            addMarker({coords:event.latLng});
        });

        {{--var markers = [--}}
            {{--{--}}
                {{--coords:{lat:21.017079, lng:105.783594},--}}
                {{--iconImage: '{{ asset('assets/img/car.png') }}',--}}
                {{--content:--}}
//                '<ul>' +
//                '<li>Type: Flatbed</li>' +
//                '<li>Plate No: H 73021</li>' +
//                '<li>Driver ID: 00000DR564</li>' +
//                '<li>Driver Name: Yoona</>' +
//                '<li>Phone: +971 500000087</li>' +
//                '</ul>'
            {{--}            --}}
        {{--];--}}

        if (typeof markers !== "undefined") {
            markers = JSON.parse(markers);
            for(var i=0; i < markers.length; i++) {
                addMarker(markers[i]);
            }
        }

        function addMarker(props) {
            var marker = new google.maps.Marker({
                position: props.coords,
                map: map
            });
            if(props.iconImage){
                marker.setIcon(props.iconImage);
            }
            if(props.content){
                var infoWindow = new google.maps.InfoWindow({
                    content: props.content
                })
            }
            marker.addListener('click', function () {
                infoWindow.open(map, marker);
            });
        }
    }
</script>
<script async defer
    src="https://maps.googleapis.com/maps/api/js?key={{ GOOGLE_API_KEY }}&callback=initMap">
</script>
<script type="text/javascript">
    $(function () {
        {{--$('#content_header').html('{{ trans('dashboard.content header') }}');--}}
    });
</script>
@endpush