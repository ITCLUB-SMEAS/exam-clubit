<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::withCount('students')
            ->when(request()->q, fn($q) => $q->where('name', 'like', '%'.request()->q.'%'))
            ->latest()
            ->paginate(10);

        $rooms->appends(['q' => request()->q]);

        return inertia('Admin/Rooms/Index', [
            'rooms' => $rooms,
        ]);
    }

    public function create()
    {
        return inertia('Admin/Rooms/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:rooms',
            'capacity' => 'required|integer|min:1'
        ]);

        Room::create($request->only('name', 'capacity'));

        return redirect()->route('admin.rooms.index');
    }

    public function edit(Room $room)
    {
        return inertia('Admin/Rooms/Edit', [
            'room' => $room,
        ]);
    }

    public function update(Request $request, Room $room)
    {
        $request->validate([
            'name' => 'required|string|unique:rooms,name,'.$room->id,
            'capacity' => 'required|integer|min:1'
        ]);

        $room->update($request->only('name', 'capacity'));

        return redirect()->route('admin.rooms.index');
    }

    public function destroy(Room $room)
    {
        $room->delete();
        return redirect()->route('admin.rooms.index');
    }
}
