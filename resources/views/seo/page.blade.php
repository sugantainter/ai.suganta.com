@extends('layouts.seo')

@section('content')
<!-- Hero Section -->
<section class="hero-section">
    <div class="category-badge">{{ $content['hero']['category'] }}</div>
    <h1 class="hero-title">{!! $content['hero']['title'] !!}</h1>
    <p class="hero-desc">{!! $content['hero']['description'] !!}</p>
    
    <div class="meta-row">
        <div class="meta-item">
            <span class="meta-icon">📅</span>
            <span>Updated: {{ $content['hero']['update_date'] }}</span>
        </div>
        <div class="meta-item">
            <span class="meta-icon">⏱️</span>
            <span>{{ $content['hero']['read_time'] }} min read</span>
        </div>
        <div class="meta-item">
            <span class="meta-icon">✓</span>
            <span>Fact Checked</span>
        </div>
    </div>
</section>

<!-- Content Grid Layout -->
<div class="page-grid">
    <!-- Main Content Column -->
    <article>
        @foreach($content['sections'] as $id => $section)
            <section id="{{ $id }}" class="article-section">
                <h2>{!! $section['title'] !!}</h2>
                <div>
                    {!! $section['body'] !!}
                </div>
            </section>
        @endforeach

        <!-- FAQ Section -->
        @if(!empty($content['faqs']))
            <section id="faqs" class="article-section">
                <h2>Frequently Asked Questions</h2>
                <p>Find answers to common questions about this topic to help you choose the best tools for your workflow.</p>
                <div class="faq-container">
                    @foreach($content['faqs'] as $faq)
                        <div class="faq-item">
                            <button class="faq-trigger" aria-expanded="false">
                                {{ $faq['question'] }}
                            </button>
                            <div class="faq-content">
                                <p>{{ $faq['answer'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <!-- Internal Links Section -->
        @if(!empty($content['related_links']))
            <section id="related-resources" class="article-section">
                <h2>Related Resources & Comparisons</h2>
                <p>Explore more in-depth reviews, step-by-step guides, and comparison tables designed to optimize your workflow:</p>
                <div class="related-links-grid">
                    @foreach($content['related_links'] as $link)
                        <a href="{{ $link['url'] }}" class="related-link-card">
                            {{ $link['title'] }}
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </article>

    <!-- Sidebar Column -->
    <aside>
        <!-- Table of Contents -->
        <div class="sidebar-card">
            <h3>On This Page</h3>
            <ul class="toc-list">
                @foreach($content['table_of_contents'] as $toc)
                    <li class="toc-item">
                        <a href="#{{ $toc['id'] }}">{{ $toc['title'] }}</a>
                    </li>
                @endforeach
                @if(!empty($content['faqs']))
                    <li class="toc-item">
                        <a href="#faqs">FAQs</a>
                    </li>
                @endif
                @if(!empty($content['related_links']))
                    <li class="toc-item">
                        <a href="#related-resources">Related Resources</a>
                    </li>
                @endif
            </ul>
        </div>

        <!-- Trending Sidebar Widget -->
        @if(!empty($content['trending_tools']))
            <div class="sidebar-card">
                <h3>Trending AI Tools</h3>
                <div class="trending-list">
                    @foreach($content['trending_tools'] as $tool)
                        <div class="trending-row">
                            <div>
                                <span class="trending-name">{{ $tool['name'] }}</span>
                                <div class="trending-category">{{ $tool['category'] }}</div>
                            </div>
                            <span class="badge-win badge-rating">{{ $tool['rating'] }} ⭐</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </aside>
</div>
@endsection
