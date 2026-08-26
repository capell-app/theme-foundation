{{-- Test-only stand-in for the Foundation widget wrapper. The real wrapper
     needs a persisted Widget model, the Frontend facade and Layout Builder
     container resolution; none of that is relevant to the URL-sanitising
     assertions, so it is replaced with a bare slot for these renders. --}}
<div class="test-widget-wrapper">{{ $slot }}</div>
