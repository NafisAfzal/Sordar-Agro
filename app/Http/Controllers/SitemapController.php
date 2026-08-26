<?php

namespace App\Http\Controllers;

use App\Models\CareGuide;
use App\Models\Product;
use Illuminate\Http\Response;

/**
 * Generates the public XML sitemap from existing, already-public data.
 * Read-only: exposes URLs and public metadata only — no user, order or
 * internal-ID information.
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        $products = Product::approved()
            ->with('category')
            ->orderByDesc('updated_at')
            ->get(['id', 'slug', 'updated_at']);

        $guides = CareGuide::whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('updated_at')
            ->get(['id', 'slug', 'updated_at']);

        $urls = collect([
            ['loc' => route('home'), 'lastmod' => null],
            ['loc' => route('products.index'), 'lastmod' => null],
            ['loc' => route('care.index'), 'lastmod' => null],
            ['loc' => route('community.index'), 'lastmod' => null],
        ])
            ->merge($products->map(fn (Product $p) => [
                'loc'     => route('products.show', $p),
                'lastmod' => $p->updated_at,
            ]))
            ->merge($guides->map(fn (CareGuide $g) => [
                'loc'     => route('care.show', $g),
                'lastmod' => $g->updated_at,
            ]));

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * Dynamic robots.txt so the Sitemap directive always points at the
     * correct deployment domain (a static file could not do this).
     */
    public function robots(): Response
    {
        $lines = collect([
            'User-agent: *',
            '# Public storefront (/, /products, /products/*, /care-guides, /community) stays crawlable.',
        ])
            ->merge(collect([
                '/admin', '/seller', '/dashboard', '/cart', '/checkout',
                '/payment', '/orders', '/wishlist', '/login', '/register',
                '/password', '/email', '/search', '/community/submit',
            ])->map(fn ($path) => "Disallow: {$path}"))
            ->push('Sitemap: '.route('sitemap.index'));

        return response($lines->implode("\n")."\n")
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
