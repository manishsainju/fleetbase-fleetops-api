<?php

namespace Fleetbase\FleetOps\Http\Controllers\Internal\v1;

use Fleetbase\FleetOps\Http\Controllers\FleetOpsController;
use Fleetbase\Http\Requests\Internal\BulkDeleteRequest;
use Fleetbase\Http\Requests\ExportRequest;
use Fleetbase\FleetOps\Exports\PlaceExport;
use Fleetbase\FleetOps\Models\Place;
use Fleetbase\FleetOps\Support\Geocoding;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Maatwebsite\Excel\Facades\Excel;

class PlaceController extends FleetOpsController
{
    /**
     * The resource to query
     *
     * @var string
     */
    public $resource = 'place';

    /**
     * Quick search places for selection
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        $searchQuery = $request->searchQuery();
        $limit = $request->input('limit', 30);
        $geo = $request->boolean('geo');
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        $query = Place::where('company_uuid', session('company'))
            ->whereNull('deleted_at')
            ->search($searchQuery);

        if ($latitude && $longitude) {
            $point = new Point($latitude, $longitude);
            $query->orderByDistanceSphere('location', $point, 'asc');
        } else {
            $query->orderBy('name', 'desc');
        }

        if ($limit) {
            $query->limit($limit);
        }

        $results = $query->get();

        if ($geo) {
            if ($searchQuery) {
                try {
                    $geocodingResults = Geocoding::query($searchQuery, $latitude, $longitude);

                    foreach ($geocodingResults as $result) {
                        $results->push($result);
                    }
                } catch (\Throwable $e) {
                    return response()->error($e->getMessage());
                }
            } else if ($latitude && $longitude) {
                try {
                    $geocodingResults = Geocoding::reverseFromCoordinates($latitude, $longitude, $searchQuery);

                    foreach ($geocodingResults as $result) {
                        $results->push($result);
                    }
                } catch (\Throwable $e) {
                    return response()->error($e->getMessage());
                }
            }
        }

        return response()->json($results);
    }

    /**
     * Search using geocoder for addresses.
     *
     * @param  \Illuminate\Http\Request  $query
     * @return \Illuminate\Http\Response
     */
    public function geocode(ExportRequest $request)
    {
        $searchQuery = $request->searchQuery();
        $latitude = $request->input('latitude', false);
        $longitude = $request->input('longitude', false);
        $results = collect();

        if ($searchQuery) {
            try {
                $geocodingResults = Geocoding::query($searchQuery, $latitude, $longitude);
                
                foreach ($geocodingResults as $result) {
                    $results->push($result);
                }
            } catch (\Throwable $e) {
                return response()->error($e->getMessage());
            }
        } else if ($latitude && $longitude) {
            try {
                $geocodingResults = Geocoding::reverseFromCoordinates($latitude, $longitude, $searchQuery);

                foreach ($geocodingResults as $result) {
                    $results->push($result);
                }
            } catch (\Throwable $e) {
                return response()->error($e->getMessage());
            }
        }

        return response()->json($results);
    }

    /**
     * Export the places to excel or csv
     *
     * @param  \Illuminate\Http\Request  $query
     * @return \Illuminate\Http\Response
     */
    public function export(ExportRequest $request)
    {
        $format = $request->input('format', 'xlsx');
        $fileName = trim(Str::slug('places-' . date('Y-m-d-H:i')) . '.' . $format);

        return Excel::download(new PlaceExport(), $fileName);
    }

    /**
     * Bulk deletes resources.
     *
     * @param  \Fleetbase\Http\Requests\Internal\BulkDeleteRequest $request
     * @return \Illuminate\Http\Response
     */
    public function bulkDelete(BulkDeleteRequest $request)
    {
        $ids = $request->input('ids', []);

        if (!$ids) {
            return response()->error('Nothing to delete.');
        }

        /** 
         * @var \Fleetbase\Models\Place 
         */
        $count = Place::whereIn('uuid', $ids)->count();
        $deleted = Place::whereIn('uuid', $ids)->delete();

        if (!$deleted) {
            return response()->error('Failed to bulk delete places.');
        }

        return response()->json(
            [
                'status' => 'OK',
                'message' => 'Deleted ' . $count . ' places',
            ],
            200
        );
    }
}
