<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;

class PostController extends Controller
{
    public function index(){
        $posts = Post::all();
        return response()->json($posts);
    }

    public function store(Request $request){
        $request->validate([
            'content' => 'required|string',
            'weight' => 'required|numeric',
            'creation_date' => 'required|date',
            'supports' => 'nullable|array',
            'media' => 'nullable|array',
            'mentions' => 'nullable|array',
            'categories' => 'nullable|array',
            'comments' => 'nullable|array',
        ]);

        $post = Post::create([
            'content' => $request->content,
            'weight' => $request->weight,
            'creation_date' => $request->creation_date,
            'supports' => $request->supports,
            'media' => $request->media,
            'mentions' => $request->mentions,
            'categories' => $request->categories,
            'comments' => $request->comments,
        ]);

        return response()->json($post, 201);
    }

    public function destroy($id){
        $post = Post::findOrFail($id);
        $post->delete();
        return response()->json(null, 204);
    }

    public function show($id){
        $post = Post::findOrFail($id);
        return response()->json($post);
    }

    public function update(Request $request, $id){
        $request->validate([
            'content' => 'required|string',
            'weight' => 'required|numeric',
            'creation_date' => 'required|date',
            'supports' => 'nullable|array',
            'media' => 'nullable|array',
            'mentions' => 'nullable|array',
            'categories' => 'nullable|array',
            'comments' => 'nullable|array',
        ]);

        $post = Post::findOrFail($id);
        $post->update([
            'content' => $request->content,
            'weight' => $request->weight,
            'creation_date' => $request->creation_date,
            'supports' => $request->supports,
            'media' => $request->media,
            'mentions' => $request->mentions,
            'categories' => $request->categories,
            'comments' => $request->comments,
        ]);

        return response()->json($post);
    }
}