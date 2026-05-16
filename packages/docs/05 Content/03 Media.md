# **Moox Media Pro — Feature Set**

## **Original ideas**

1. **Translated Media**
   - Allow different files per language (e.g., German banner vs. English banner).

2. **Real Paths / Nested Set**
   - True folder hierarchy with nested set implementation and persistent “real paths” instead of only collections. SEO also ...

3. **Responsiveness, Optimization, Converters**
   - Responsive images
   - Image optimization (modern file formats)
   - Video formats, conversion
   - Office docs (pandoc)

4. **Media API**
   - API for Moox Mobile and Desktop
   - Upload files

5. **Image Editor**
   - https://github.com/scaleflex/filerobot-image-editor
   - https://github.com/nhn/tui.image-editor

6. **Image Optimization**
   - https://glide.thephpleague.com/ - offer an API for shared host people? probably not...
   - Alternatively use graphics magic, imagemagick or gdlib
   - https://github.com/spatie/image-optimizer - needs linux things running
   - https://github.com/Intervention/image - perfect couple to spatie package
   - https://github.com/Intervention/image-laravel - laravel wrapper
   - https://github.com/Intervention/gif - no dependency gif encoder
   - **Cloudinary** as a image/video API
   - For video: ffmpeg (endcoding, converter) but no mini editor UI found yet
