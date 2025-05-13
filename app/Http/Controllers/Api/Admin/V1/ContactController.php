<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V1\ContactResource;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * Display a listing of the contacts.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $contacts = Contact::latest()->paginate(10);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Contacts retrieved successfully',
            'data' => ContactResource::collection($contacts),
            'meta' => [
                'total' => $contacts->total(),
                'current_page' => $contacts->currentPage(),
                'last_page' => $contacts->lastPage(),
                'per_page' => $contacts->perPage()
            ]
        ]);
    }

    /**
     * Display the specified contact.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $contact = Contact::findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Contact retrieved successfully',
            'data' => new ContactResource($contact)
        ]);
    }

    /**
     * Remove the specified contact from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $contact = Contact::findOrFail($id);
        $contact->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Contact deleted successfully'
        ]);
    }
}
