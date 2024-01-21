<?php

namespace App\Http\Controllers\API\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BaseController extends Controller
{
    public function index()
    {
        $items = $this->model::with($this->with ?? [])->paginate(request("page_size", 10));

        return successResponse($items);
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
