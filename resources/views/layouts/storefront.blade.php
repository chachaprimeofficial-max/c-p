<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(session('locale', app()->getLocale()), ['ur', 'ar']) ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title', 'ChachaPrime — Global sourcing, made simple')</title>
<meta name="description" content="@yield('description', 'Discover quality products from China for retail, wholesale and group buying.')">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
<link rel="stylesheet" href="{{ asset('css/commerce.css') }}">
</head>
<body class="storefront">
<div class="announcement"><span>{{ __('Global sourcing from China') }}</span><span>{{ __('Wholesale prices · Trusted support · Worldwide delivery') }}</span></div>
<header class="site-header">
<a class="brand" href="{{ route('home') }}" aria-label="ChachaPrime home"><span class="brand-mark">CP</span><span>CHACHA<span class="brand-light">PRIME</span></span></a>
<form class="header-search" action="{{ route('products.index') }}" method="get"><span class="search-icon">⌕</span><input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Search products, brands and more...') }}" aria-label="Search products"><button type="submit">{{ __('Search') }}</button></form>
<div class="header-actions">
<label class="header-select"><span>{{ __('Language') }}</span><select aria-label="Language" onchange="if(this.value) location.href='/locale/'+this.value">@foreach(config('marketplace.locales') as $code => $name)<option value="{{ $code }}" @selected(session('locale', app()->getLocale()) === $code)>{{ $name }}</option>@endforeach</select></label>
<label class="header-select"><span>{{ __('Currency') }}</span><select aria-label="Currency" onchange="if(this.value) location.href='/currency/'+this.value">@foreach(config('marketplace.currencies') as $code => $currency)<option value="{{ $code }}" @selected(session('currency', 'USD') === $code)>{{ $code }}</option>@endforeach</select></label>
<a class="header-account" href="{{ auth()->check() && !auth()->user()->is_admin ? route('account.orders.index') : route('account.login') }}"><span class="account-icon">♙</span><span>{{ __('Account') }}<small>{{ auth()->check() && !auth()->user()->is_admin ? __('My orders') : __('Sign in') }}</small></span></a><a class="cart-link" href="{{ route('cart.index') }}" aria-label="Shopping cart"><span>▱</span><b>{{ __('Cart') }}</b><small>{{ array_sum(session('cart', [])) }}</small></a>
</div></header>
<nav class="main-nav"><a href="{{ route('products.index') }}">{{ __('All products') }}</a><a href="{{ route('products.index', ['b2b' => 1]) }}">{{ __('Wholesale & B2B') }}</a><a href="{{ route('products.index', ['group_buy' => 1]) }}">{{ __('Group buying') }}</a><a href="#categories">{{ __('Categories') }}</a><a href="#benefits">{{ __('Why ChachaPrime') }}</a><span class="nav-spacer"></span><span class="nav-note">{{ __('China sourcing, globally connected') }}</span></nav>
<main>@yield('content')</main>
<footer class="site-footer"><div class="footer-main">
<div class="footer-brand"><a class="brand brand-inverse" href="{{ route('home') }}"><span class="brand-mark">CP</span><span>CHACHA<span class="brand-light">PRIME</span></span></a><p>{{ __('Your bridge to quality products and trusted sourcing from China.') }}</p></div>
<div><h3>{{ __('Explore') }}</h3><a href="{{ route('products.index') }}">{{ __('All products') }}</a><a href="{{ route('products.index', ['b2b' => 1]) }}">{{ __('B2B wholesale') }}</a><a href="{{ route('products.index', ['group_buy' => 1]) }}">{{ __('Group buying') }}</a></div>
<div><h3>{{ __('Customer care') }}</h3><a href="mailto:support@chachaprime.com">{{ __('Contact support') }}</a><a href="mailto:info@chachaprime.com">{{ __('Business enquiries') }}</a><a href="#returns">{{ __('Returns & warranty') }}</a></div>
<div><h3>{{ __('For business') }}</h3><a href="mailto:info@chachaprime.com">{{ __('Source products') }}</a><a href="mailto:info@chachaprime.com">{{ __('Factory enquiries') }}</a><a href="{{ route('admin.login') }}">{{ __('Administration') }}</a></div>
</div><div class="footer-bottom"><span>© {{ date('Y') }} Yiwu Chacha International Trade Co., Ltd.</span><span>{{ __('Secure shopping · Clear pricing · Global reach') }}</span></div></footer>
<script src="{{ asset('js/app.js') }}" defer></script></body></html>