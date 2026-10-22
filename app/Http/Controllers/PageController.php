<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public const PAGES = [
        'about' => 'About Us',
        'contact' => 'Contact Us',
        'faqs' => 'FAQs',
        'shipping' => 'Shipping & Delivery',
        'returns' => 'Returns & Exchange',
        'privacy' => 'Privacy Policy',
        'terms' => 'Terms & Conditions',
    ];

    public function show(string $page)
    {
        abort_unless(isset(self::PAGES[$page]), 404);

        return view('store.page', ['page' => $page, 'title' => self::PAGES[$page], 'pages' => self::PAGES]);
    }

    public function contact(Request $request)
    {
        $request->merge(['phone' => normalize_phone($request->input('phone'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'regex:/^03\d{9}$/'],
            'subject' => ['nullable', 'string', 'max:120'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ], ['phone.regex' => 'Enter a valid mobile number, for example 0300 1234567.']);
        ContactMessage::create($data);

        return back()->with('toast', 'Message received. Our team will contact you within one working day.');
    }
}
