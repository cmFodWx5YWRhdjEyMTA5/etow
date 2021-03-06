<?php
/**
 * Created by PhpStorm.
 * User: tienvm
 * Date: 12/21/17
 * Time: 10:36 AM
 */

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\ApiBaseController;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Driver;
use App\Models\Otp;
use App\Models\Trip;
use App\Models\UserToken;
use App\Transformers\Api\AccountTransformer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Tymon\JWTAuth\Exceptions\JWTException;
use \Illuminate\Support\Facades\Auth;

class DriverController extends Controller
{
    private $driver;
    private $account;
    private $trip;
    public function __construct(Account $account, Driver $driver, Trip $trip){
        $this->account = $account;
        $this->driver = $driver;
        $this->trip = $trip;
    }

    public function search(Request $request) {
        $key  = $request->input('key');
        $type = $request->input('type');

        $orderName = $request->input('order');
        if ($orderName) {
            $order = $orderName[0]['dir'];
            $column = $orderName[0]['column'];
        } else {
            $order = null;
            $column = null;
        }

        $params = compact('key', 'type');
        $offset = $request->input('start');
        $limit  = $request->input('length');
        $data   = [];
        $total  = 0;

        $drivers = $this->driver->search($params, $order, $column, $offset, $limit, false);
        list($totalAll, $totalOnline, $totalOffline, $totalFree, $totalOnTrip) =
            $this->getTotalByType($params, $order, $column, $offset, $limit);
        if($drivers->isEmpty() == true){
            return $this->_getResponse($request, $total, $data, $totalAll, $totalOnline, $totalOffline, $totalFree, $totalOnTrip);
        }

        $i = $request->input('start');
        foreach ($drivers as $driver){
            $driver_status_label  = $driver->is_online == 1 ? 'Online' : 'Offline';
            $driver_status_class  = $driver->is_online == 0 ? 'label-danger' : 'label-success';

            $status_label  = $driver->userR->status == 1 ? 'Activate' : 'Deactivate';
            $status_class  = $driver->userR->status == 0 ? 'btn-danger' : 'btn-success';
            $status_option_label = $driver->userR->status == 0 ? 'Activate' : 'Deactivate';
            $status_option_value = $driver->userR->status == 0 ? STATUS_ACTIVE : STATUS_INACTIVE;

            $i++;
            $tmp = [
                $i,
                '<a href="'.route('trip.by_driver_type',[$driver->user_id, TRIP_COMPLETE]).'" target="_blank" class="">'.
                    $driver->driver_code.
                '</span></a>',
                $driver->userR ?
                    ('<a href="'.route('trip.by_driver_type',[$driver->user_id, TRIP_COMPLETE]).'" target="_blank" class="">'.
                        $driver->userR->full_name.
                    '</span></a>') : '',
                ($driver->userR && $driver->userR->avatar) ?
                "<img src='".asset('upload/account/'.$driver->userR->avatar)."' class='img-responsive' />" : '',
                $driver->userR ? $driver->userR->email : '',
                $this->getLabelTypeVehicle($driver->vehicle_type),
                $driver->vehicle_number,
                $driver->company_name,
                ($driver->driver_license) ?
                    "<img src='".asset('upload/driver/'.$driver->driver_license)."' class='img-responsive' />" : '',
                ($driver->emirate_id) ?
                    "<img src='".asset('upload/driver/'.$driver->emirate_id)."' class='img-responsive' />" : '',
                ($driver->mulkiya) ?
                    "<img src='".asset('upload/driver/'.$driver->mulkiya)."' class='img-responsive' />" : '',
                "<span class='label $driver_status_class'>".$driver_status_label."</span>",
                '<a href="'.route('trip.by_driver_type',[$driver->user_id, TRIP_COMPLETE]).'" target="_blank" class="btn btn-success">'.
                    $this->trip->totalTripByDriverAndStatus($driver->user_id, [TRIP_STATUS_COMPLETED]).
                '</span></a>',
                '<a href="'.route('trip.by_driver_type',[$driver->user_id, TRIP_CANCEL]).'" target="_blank" class="btn btn-danger">'.
                    $this->trip->totalTripByDriverAndStatus($driver->user_id, [TRIP_STATUS_CANCEL]).
                '</span></a>',
                '<a href="'.route('trip.by_driver_type',[$driver->user_id, TRIP_REJECT]).'" target="_blank" class="btn btn-danger">'.
                    $this->trip->totalTripByDriverAndStatus($driver->user_id, [TRIP_STATUS_REJECT]).
                '</span></a>',
                '<a href="'.route('trip.by_driver_type',[$driver->user_id, TRIP_SCHEDULE]).'" target="_blank" class="btn btn-primary">'.
                    $this->trip->totalTripByDriverAndStatus($driver->user_id, [TRIP_STATUS_NEW]).
                '</span></a>',
                '<a href="'.route('trip.by_driver_type',[$driver->user_id, TRIP_ON_GOING]).'" target="_blank" class="btn btn-warning">'.
                    $this->trip->totalTripByDriverAndStatus($driver->user_id, [ TRIP_STATUS_ACCEPT, TRIP_STATUS_ARRIVED, TRIP_STATUS_JOURNEY_COMPLETED, TRIP_STATUS_ON_GOING]).
                '</span></a>',
                //"<img src='assets/img/rating.png'  alt='' />",
                $this->stars(($driver->avgRate($driver->user_id))/2),
                '<div style="margin:5px;" class="btn-group">
                    <button style="width: 105px" id="btnStatus" data-toggle="dropdown" class="btn '.$status_class.' dropdown-toggle" aria-expanded="false">
                        '.$status_label.'<span class="caret"></span></button>
                        <ul class="dropdown-menu">
                            <li><a onclick="changeStatus('.$driver->user_id.','.$status_option_value.')">'.$status_option_label.'</a></li>
                        </ul>
                </div>',
                "<div class='btn-group'>
                    <a href='".route('driver.edit', $driver->id)."' class='btn btn-warning' target='_blank'>Edit</a>
                    <button class='btn btn-danger btn-flat' data-toggle='modal' data-target='#modal-delete-confirmation' 
                    data-action-target='" . route('driver.delete', [$driver->id]) . "'><i class='fa fa-trash'></i></button>
                </div>"
            ];
            $data[] = $tmp;
        }

        $params['type'] = $request->get('type');
        $total = $this->driver->search($params, $order, $column, $offset, $limit, true);
        return $this->_getResponse($request, $total, $data, $totalAll, $totalOnline, $totalOffline, $totalFree, $totalOnTrip);
    }

    public function getTotalByType(&$params, $order, $column, $offset, $limit) {
        $params['type'] = '';
        $totalAll = $this->driver->search($params, $order, $column, $offset, $limit, true);
        $params['type'] = DRIVER_ONLINE;
        $totalOnline = $this->driver->search($params, $order, $column, $offset, $limit, true);
        $params['type'] = DRIVER_OFFLINE;
        $totalOffline = $this->driver->search($params, $order, $column, $offset, $limit, true);
        $params['type'] = FREE_DRIVER;
        $totalFree = $this->driver->search($params, $order, $column, $offset, $limit, true);
        $params['type'] = DRIVER_ON_TRIP;
        $totalOnTrip = $this->driver->search($params, $order, $column, $offset, $limit, true);
        //dd($totalOnTrip);
        return [$totalAll, $totalOnline, $totalOffline, $totalFree, $totalOnTrip];
    }

    public function _getResponse($request, $total, $data, $totalAll = 0, $totalOnline = 0, $totalOffline = 0, $totalFree = 0, $totalOnTrip = 0) {
        return response()->json([
            'draw' => $request->input('draw'),
            "recordsTotal" => $total,
            'recordsFiltered' => $total,
            'data' => $data,
            'total_all'  => $totalAll,
            'total_online' => $totalOnline,
            'total_offline' => $totalOffline,
            'total_free' => $totalFree,
            'total_on_trip' => $totalOnTrip
        ]);
    }

    public function getLabelTypeVehicle($type_vehicle) {
        return $type_vehicle == VEHICLE_TYPE_NORMAL ? 'Normal' : 'Flat Bed';
    }
}