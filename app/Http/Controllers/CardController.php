<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use App\Models\Card;
use App\Http\Resources\CardResource;

class CardController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        $itemsCard = $request->user()->card()->whereHas('product', function($query){
            $query->whereNull('deleted_at');
        })->get();

        return response()->json([
            'card' => CardResource::collection($itemsCard)
        ]);
        
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $validation = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer'
        ]);

        if ($validation->fails()) {
            
            return response()->json($validation->messages(), 203);

        } 

        $check_card_if_exists = Card::where('product_id', $request->product_id)
        ->where('user_id', $request->user()->id)
        ->first();

        if (isset($check_card_if_exists)) {
            
            $check_card_if_exists->update([
                'quantity' => $check_card_if_exists + $request->quantity
            ]);

        }else{

            $card = Card::create([
                'product_id' => $request->product_id,
                'user_id' => $request->user()->id,
                'quantity' => $request->quantity
            ]);

        }

        return response()->json([
            'card' => new CardResource($card)
        ]);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
        $validation = Validator::make($request->all(), [
            'quantity' => 'required|integer'
        ]);

        if ($validation->fails()) {
            
            return response()->json($validation->messages(), 203);

        } 

        try {

            $id = Crypt::decrypt($id);
                
            $card = Card::find($id);
    
            $card->update($request->all());
    
            return response()->json([
                'message' => 'Your Item Updated Successfully'
            ]);

        } catch (\Throwable $e) {
            
            return response()->json('Something not correct', 422);

        }


    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
        try {

            $id = Crypt::decrypt($id);
                
            $card = Card::find($id);
            $card->delete();

            return response()->json([
                'message' => 'item deleted successfully'
            ],200);

        } catch (\Throwable $e) {
            
            return response()->json('Something not correct', 422);

        }

    }

}
