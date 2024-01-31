<?php

namespace App\Http\Controllers\API\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BaseController extends Controller
{
    public function index()
    {
        $items = $this->model::with($this->with ?? []);

        $items = $this->filter($items);

        $items = $items->paginate(request("per_page", 10));

        return successResponse($items);
    }

    public function filter($items)
    {
        try {

            foreach (array_keys(request()->all()) as $filter) {
                if (in_array($filter, app($this->model)->getFillable()) && !empty(request($filter))) {
                    $items = $items->where($filter, request($filter));
                }
            }

            if (request('q') && !empty(request('q')) && isset($this->search)) {
                $items = $items->where(function ($q) {
                    foreach ($this->search ?? [] as $search) {
                        $q->orWhere($search, 'LIKE', '%' . request('q') . '%');
                    }
                });
            }

            if (request('date_from') && request('date_to')) {
                $items = $items->whereBetween('created_at', [request('date_from'), request('date_to')]);
            }

            return $items;
        } catch (\Throwable $th) {
            //throw $th;
            return $items;
        }
    }

    public function show($id)
    {
        $items = $this->model::with($this->with ?? [])->find($id);

        return successResponse($items);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->storeValidation());

        $item = $this->model::create($data);

        return successResponse($item);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate($this->updateValidation());

        $item = $this->model::find($id);

        $item->update($data);

        return successResponse($item);
    }

    public function destroy($id)
    {
        $item = $this->model::find($id);

        $item->delete();

        return successResponse($item);
    }
}
