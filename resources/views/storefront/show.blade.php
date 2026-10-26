@extends('layouts.storefront')

@section('title', $product->name.' | ChachaPrime')

@section('content')
<div class="breadcrumb">
    <a href="{{ route('home') }}">{{ __('Home') }}</a>
    <span>/</span>
    <a href="{{ route('products.index') }}">{{ __('All products') }}</a>
    <span>/</span>
    <span>{{ $product->name }}</span>
</div>

<section class="product-detail-layout">
    <div class="detail-gallery">
        <div class="detail-main-image">
            @if($product->media->where('type', 'image')->first())
                <img src="{{ asset('storage/'.$product->media->where('type', 'image')->first()->path) }}" alt="{{ $product->name }}">
            @else
                <div class="product-placeholder detail-placeholder"><span>CP</span></div>
            @endif
        </div>

        @if($hasExtraMedia)
            <div class="detail-media-assets">
                @foreach($product->media->where('type', 'image')->skip(1) as $media)
                    <a class="detail-media-thumb" href="{{ asset('storage/'.$media->path) }}" target="_blank" rel="noopener">
                        <img src="{{ asset('storage/'.$media->path) }}" alt="{{ $media->alt_text ?: $product->name }}">
                    </a>
                @endforeach

                @foreach($product->media->where('type', 'video') as $media)
                    <video class="detail-media-video" controls preload="metadata">
                        <source src="{{ asset('storage/'.$media->path) }}">
                    </video>
                @endforeach

                @foreach($product->media->whereIn('type', ['document', 'blueprint']) as $media)
                    <a class="detail-media-file" href="{{ asset('storage/'.$media->path) }}" target="_blank" rel="noopener">
                        {{ $media->type === 'blueprint' ? __('Blueprint') : __('Product document') }} {{ $loop->iteration }} ↗
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <div class="detail-info">
        <div class="eyebrow">{{ $product->brand_name ?: 'CHACHAPRIME SELECTION' }}</div>
        <h1>{{ $product->name }}</h1>
        <div class="detail-meta">
            <span>{{ __('Product ID:') }} {{ $product->product_code }}</span>
            <span>{{ $product->stock > 0 ? __('In stock') : __('Contact for availability') }}</span>
        </div>
        <div class="detail-price">
            {{ $product->display_currency }} {{ number_format((float) $product->display_price, 2) }}
            <small>/ {{ __('unit retail') }}</small>
        </div>

        @if($product->b2b_enabled && $product->priceTiers->isNotEmpty())
            <div class="pricing-panel">
                <div class="pricing-panel-head">
                    <strong>{{ __('Wholesale price breaks') }}</strong>
                    <span>{{ __('Buy more, save more') }}</span>
                </div>
                <div class="tier-table">
                    <div class="tier-row tier-header">
                        <span>{{ __('Quantity') }}</span>
                        <span>{{ __('Price per unit') }}</span>
                    </div>
                    @foreach($product->priceTiers as $tier)
                        <div class="tier-row">
                            <span>{{ $tier->min_quantity }}{{ $tier->max_quantity ? '–'.$tier->max_quantity : '+' }} {{ __('units') }}</span>
                            <strong>{{ $tier->display_currency }} {{ number_format((float) $tier->display_price, 2) }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($product->group_buy_enabled)
            <div class="group-buy-note">
                <strong>{{ __('Group-buy opportunity') }}</strong>
                @forelse($activeCampaigns as $campaign)
                    <div class="group-campaign">
                        <strong>{{ $campaign->title }}</strong>
                        <span>{{ $campaign->display_currency }} {{ number_format((float) $campaign->display_price, 2) }} / {{ __('unit') }}</span>
                        <small>{{ $campaign->current_participants }} / {{ $campaign->target_participants }} {{ __('participants') }} · {{ __('Ends') }} {{ $campaign->ends_at?->format('M j, Y') ?? __('when target is reached') }}</small>
                    </div>
                @empty
                    <p>{{ __('Ask our team about current group-buy availability and target pricing.') }}</p>
                @endforelse
            </div>
        @endif

        <div class="detail-availability">
            <span>✓</span>
            <div>
                <strong>{{ __('Product information available') }}</strong>
                <small>{{ __('Shipping is estimated at checkout; final carrier and customs charges may need confirmation.') }}</small>
            </div>
        </div>

        @if($hasPurchasableStock)
            <form class="add-to-cart-form" method="post" action="{{ route('cart.add', $product) }}">
                @csrf

                @if($activeCampaigns->isNotEmpty())
                    <label class="field-label field-span-2">
                        {{ __('Pricing option') }}
                        <select name="campaign_id">
                            <option value="">{{ __('Standard retail / quantity pricing') }}</option>
                            @foreach($activeCampaigns as $campaign)
                                <option value="{{ $campaign->id }}">
                                    {{ $campaign->title }} — {{ $campaign->display_currency }} {{ number_format((float) $campaign->display_price, 2) }} / {{ __('unit') }}
                                    ({{ $campaign->current_participants }}/{{ $campaign->target_participants }} {{ __('joined') }})
                                </option>
                            @endforeach
                        </select>
                        <small>{{ __('Select a group-buy offer to apply its campaign price to this cart line.') }}</small>
                    </label>
                @endif

                @if($product->variants->isNotEmpty())
                    <label class="field-label field-span-2">
                        {{ __('Choose option') }}
                        <select name="variant_id" required>
                            <option value="">{{ __('Select a variation') }}</option>
                            @foreach($product->variants as $variant)
                                <option value="{{ $variant->id }}" data-stock="{{ $variant->stock }}" @disabled($variant->stock < 1)>
                                    {{ $variant->name }} · {{ $variant->stock }} {{ __('available') }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                @endif

                <label class="field-label">
                    {{ __('Quantity') }}
                    <input type="number" name="quantity" min="1" max="{{ $product->variants->isNotEmpty() ? $product->variants->max('stock') : $product->stock }}" value="1" required>
                </label>
                <button class="btn btn-primary btn-full" type="submit">{{ __('Add to cart') }} <span>＋</span></button>
            </form>
        @else
            <button class="btn btn-secondary btn-full" type="button" disabled>{{ __('Currently unavailable') }}</button>
        @endif

        <a class="btn btn-secondary btn-full" href="mailto:info@chachaprime.com?subject={{ rawurlencode('Product enquiry: '.$product->name) }}">
            {{ __('Enquire about this product') }} <span>↗</span>
        </a>
        <p class="detail-small">{{ __('Secure order details and shipping terms will be confirmed before payment.') }}</p>
    </div>
</section>

<section class="detail-description">
    <div class="detail-tabs">
        <span class="active">{{ __('Description') }}</span>
        <span>{{ __('Specifications') }}</span>
        <span>{{ __('Shipping & returns') }}</span>
    </div>
    <div class="description-grid">
        <div>
            <h2>{{ __('About this product') }}</h2>
            <p>{{ $product->description ?: __('Detailed product information will be provided by our product team.') }}</p>
            @if($product->specification)
                <h3>{{ __('Specifications') }}</h3>
                <p>{{ $product->specification }}</p>
            @endif
        </div>
        <aside>
            <h3>{{ __('Product details') }}</h3>
            <dl>
                <dt>{{ __('Brand') }}</dt>
                <dd>{{ $product->brand_name ?: __('Not specified') }}</dd>
                <dt>{{ __('Country of origin') }}</dt>
                <dd>{{ $product->country_of_origin ?: __('To be confirmed') }}</dd>
                <dt>{{ __('Handling time') }}</dt>
                <dd>{{ $product->handling_time_days }} {{ __('business days') }}</dd>
                <dt>{{ __('Warranty') }}</dt>
                <dd>{{ $product->warranty ?: __('Confirm before ordering') }}</dd>
            </dl>
        </aside>
    </div>
</section>
@endsection
