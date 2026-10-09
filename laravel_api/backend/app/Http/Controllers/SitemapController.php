<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class SitemapController extends Controller
{
    public function index()
    {
        $baseUrl = 'https://titjaffna.lk';

        $routes = [
            '/',
            '/about',
            '/contact',
            '/classes',
            '/blogs',
            '/blog/1',
            '/blog/2',
            '/blog/3',
            '/blog/4',
            '/notes',
            '/past-papers',
            '/recordings',
            '/exam-results',
            '/tutor-apply',
            '/privacy',
            '/terms',
            '/refund',
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($routes as $route) {
            $xml .= "\n  <url>";
            $xml .= "\n    <loc>" . $baseUrl . $route . "</loc>";
            $xml .= "\n    <changefreq>weekly</changefreq>";
            $xml .= "\n    <priority>" . ($route == '/' ? '1.0' : '0.8') . "</priority>";
            $xml .= "\n  </url>";
        }

        $xml .= "\n</urlset>";

        return Response::make($xml, 200, [
            'Content-Type' => 'application/xml'
        ]);
    }
}
