<?php

namespace Fleetbase\Http\Controllers\Internal\v1;

use Fleetbase\Http\Controllers\FleetbaseController;
use Fleetbase\Models\IntegratedVendor;
use Fleetbase\Support\IntegratedVendors;
use Fleetbase\Http\Requests\Internal\BulkDeleteRequest;
use Illuminate\Http\Request;

class IntegratedVendorController extends FleetbaseController
{
    /**
     * The resource to query
     *
     * @var string
     */
    public $resource = 'integrated_vendor';

    /**
     * Get available integrated vendors
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getSupported(Request $request)
    {
        $supported = IntegratedVendors::all()->map(function ($vendor) {
            return $vendor->toArray();
        });

        return response()->json($supported);
    }

    /**
     * Bulk delete resources.
     *
     * @param  \Fleetbase\Http\Requests\Internal\BulkDeleteRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkDelete(BulkDeleteRequest $request)
    {
        $ids = $request->input('ids', []);

        if (!$ids) {
            return response()->error('Nothing to delete.');
        }

        /** @var \Fleetbase\Models\IntegratedVendor */
        $count = IntegratedVendor::whereIn('uuid', $ids)->count();
        $deleted = IntegratedVendor::whereIn('uuid', $ids)->delete();

        if (!$deleted) {
            return response()->error('Failed to bulk delete vendors.');
        }

        return response()->json(
            [
                'status' => 'OK',
                'message' => 'Deleted ' . $count . ' integrated vendors',
            ],
            200
        );
    }
}
