<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    public function index(){

        $search = request('search');

        if($search){

            $events = Event::where([
                ['title', 'like', '%'.$search. '%']
            ])->get();

        }else{
            $events = Event::all();
        }

       

        return view('welcome', ['events' => $events, 'search' => $search]);

    }

    public function create(){
        return view('events.create');
    }

    public function store(Request $request){


        $validedData = $request->validate([

            'title' => 'required|string|max:255',
        'city' => 'required|string|max:255',
        'description' => 'required|string',
        'date' => 'required|date',

        ]);

        $event = new Event;

        $event->title = $request->title;
        $event->date = $request->input('date');
        $event->city = $request->city;
        $event->private = $request->private;
        $event->description = $request->description;
        $event->items = $request->items;

        //image upload
        if($request->hasfile('image') && $request->file('image')->isValid()){

            $requestImage = $request->image;

            $extension = $requestImage->extension();

            $imageName = md5($requestImage->getClientOriginalName() . strtotime("now")) . "." . $extension;

            $requestImage->move(public_path('img/events'), $imageName);

            $event->image = $imageName;

        }

        $user = auth()->user();
        $event->user_id = $user->id;

        $event->save();

        return redirect('/')->with('msg', 'Evento criado com sucesso!');

    }

    public function show($id){

        $event = Event::findOrFail($id);

        $user = auth()->user();
        $hasUserJoined = false;

        if($user) {

            $userEvents = $user->eventsAsParticipant->toArray();

            foreach($userEvents as $userEvent){
                if($userEvent['id'] == $id){
                    $hasUserJoined = true;
                }
            }

        }

        $eventOwner = User::where('id', $event->user_id)->first()->toArray();

        return view('events.show', ['event' => $event, 'eventOwner' => $eventOwner, 'hasUserJoined' => $hasUserJoined]);

    }

    public function dashboard(){

        $user = auth()->user();

        $events = $user->events;

        $eventsAsParticipant = $user->eventsAsParticipant;

        return view('events.dashboard', 
            ['events' => $events, 'eventsasparticipant' => $eventsAsParticipant]);

    }

    public function destroy($id){


        Event::findOrFail($id)->delete();

        DB::table('event_user')->where('event_id', $id)->delete();

        return redirect('/dashboard')->with('msg', 'Evento excluido com sucesso!');

    }

    public function edit($id){

        $user = auth()->user();

        $event = Event::findOrFail($id);

        if($user->id != $event->user_id){
            return redirect('/dashboard');
        }
    
        return view('events.edit', ['event' => $event]);    
    
    }

    public function update(Request $request, $id)
    {
        
        $data = $request->all();

        /* imagem upload  */

        if($request->hasfile('image') && $request->file('image')->isValid()){

            $requestImage = $request->image;

            $extension = $requestImage->extension();

            $imageName = md5($requestImage->getClientOriginalName() . strtotime("now")) . "." . $extension;

            $requestImage->move(public_path('img/events'), $imageName);

            $data['image'] = $imageName;

        $event = Event::findOrFail($request->id)->update($data);
       
        return redirect('/dashboard')->with('msg', 'Evento editado com sucesso!');

        }

    }

    public function joinEvent($id){
            
        $user = auth()->user();

        $user->eventsAsParticipant()->attach($id);

        $event = Event::findOrFail($id);

        return redirect('/dashboard')->with('msg', 'Sua presença está confirmada no eventos: ' . $event->title);

    }

    public function leaveEvent($id){

        $user = auth()->user();

        $user->eventsAsParticipant()->detach($id);

        $event = Event::findOrFail($id);

        return redirect('/dashboard')->with('msg', 'Você saiu com sucesso do evento: ' . $event->title);

    }

} 
