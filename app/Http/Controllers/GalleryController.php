<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GalleryController
{
    use ApiResponse;

    public function index()
    {
        try {
        } catch (\Exception $e) {
            Log::error('Error during fethcing tags: ' . $e->getMessage());
            return $this->sendError('An error occurred while fethcing tags');
        }
    }

    public function store(Request $request)
    {
        try {
        } catch (\Exception $e) {
            Log::error('Error during creating gallery: ' . $e->getMessage());
            return $this->sendError('An error occurred while creating gallery');
        }
    }

    public function show($id)
    {
        try {
        } catch (\Exception $e) {
            Log::error('Error during showing gallery: ' . $e->getMessage());
            return $this->sendError('An error occurred while showing gallery');
        }
    }

    public function update(Request $request, $id)
    {
        try {
        } catch (\Exception $e) {
            Log::error('Error during updating gallery: ' . $e->getMessage());
            return $this->sendError('An error occurred while updating gallery');
        }
    }

    public function destroy($id)
    {
        try {
        } catch (\Exception $e) {
            Log::error('Error during deleting gallery: ' . $e->getMessage());
            return $this->sendError('An error occurred while deleting gallery');
        }
    }

    public function trashed()
    {
        try {
        } catch (\Exception $e) {
            Log::error('Error during fetching trashed galleries: ' . $e->getMessage());
            return $this->sendError('An error occurred while fetching trashed galleries');
        }
    }

    public function restore($id)
    {
        try {
        } catch (\Exception $e) {
            Log::error('Error during restoring gallery: ' . $e->getMessage());
            return $this->sendError('An error occurred while restoring gallery');
        }
    }

    public function forceDelete($id)
    {
        try {
        } catch (\Exception $e) {
            Log::error('Error during force deleting gallery: ' . $e->getMessage());
            return $this->sendError('An error occurred while force deleting gallery');
        }
    }

    public function search(Request $request)
    {
        try {
        } catch (\Exception $e) {
            Log::error('Error during searching galleries: ' . $e->getMessage());
            return $this->sendError('An error occurred while searching galleries');
        }
    }
}
