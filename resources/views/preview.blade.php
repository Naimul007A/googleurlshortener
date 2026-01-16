<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="utf-8">

    <title>{{ $title }}</title>

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <link rel="canonical" href="{{ $url }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:image" content="{{ $image }}">
    <meta property="og:url" content="{{ $url }}">

    <!-- Optional auto redirect (humans won't see this) -->
    <meta http-equiv="refresh" content="0;url={{ $url }}">
</head>

<body></body>

</html>
