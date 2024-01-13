<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

class BaseController extends Controller
{
    public function index()
    {
        $items = $this->model::with($this->with ?? [])->get();

        return successResponse($items);
    }

    public function store(Request $request)
    {
        $request->validate($this->storeValidation());

        $data = $request->validated();

        $item = $this->model::create($data);

        return successResponse($item);
    }

    public function update(Request $request, $id)
    {
        $request->validate($this->updateValidation());

        $item = $this->model::find($id);

        $data = $request->validated();

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
