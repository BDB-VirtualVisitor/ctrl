@extends('ctrl::form_fields.master')

@section('input')
	<textarea class="form-control froala-editor" id="{{ $field['id'] }}" name="{{ $field['name'] }}">{{ $field['value'] }}</textarea>
@overwrite
{{-- Note that we need @overwrite because we include multiple instances of templates that extend form_fields.master: see https://github.com/laravel/framework/issues/1058 --}}

{{-- This allows us to push some JS to the JS stack only once; it's a hack, but appears to work --}}
@if (empty($GLOBALS['push_froala_js']))
	@push('js')
		<!-- Include JS files. -->
		<script type="text/javascript" src="{{ asset('assets/vendor/ctrl/vendor/froala3/js/froala_editor.pkgd.min.js') }}"></script>
	@endpush
	<?php $GLOBALS['push_froala_js'] = true; ?>
@endif

@push('js')
			<!-- Initialize the editor. -->
		<script>
		  $(function() {
		      // $('#{{ $field['id'] }}').froalaEditor({
				  // v3:
			var editor = new FroalaEditor('#{{ $field['id'] }}', {
				key: "HQD1uB3A5B1A1E1E2lFe1a1PVWEc1Fd1XHTHc1THMMe1NCb1tA1A1A1E1H4B1B1B1A6B5==",
				pastePlain: true,
		      	/* Should be feasible to customise this on a per-field basis? */
		      	toolbarButtons: ['fullscreen', 'bold', 'italic', 'underline', '|', 'paragraphFormat', 'align', 'formatOL', 'formatUL', 'outdent', 'indent','|',  'insertLink', 'insertImage', 'insertVideo', 'insertFile', 'insertTable' @if(config('ctrl.edit-html',false)) , '|', 'html' @endif],
				
				@if(config('ctrl.edit-html',false))
				htmlRemoveTags: [],
				@endif
		      	/* Select from:
		      	['fullscreen', 'bold', 'italic', 'underline', 'strikeThrough', 'subscript', 'superscript', 'fontFamily', 'fontSize', '|', 'color', 'emoticons', 'inlineStyle', 'paragraphStyle', '|', 'paragraphFormat', 'align', 'formatOL', 'formatUL', 'outdent', 'indent', 'quote', 'insertHR', '-', 'insertLink', 'insertImage', 'insertVideo', 'insertFile', 'insertTable', 'undo', 'redo', 'clearFormatting', 'selectAll', 'html']
		      	See: https://www.froala.com/wysiwyg-editor/docs/options#toolbarButtons
		      	*/
		      	charCounterCount: false,
		      	toolbarSticky: true, /* Was this causing layout glitches in Chrome? Nah, reckon it'll be fine */
		      	toolbarStickyOffset: 50, /* Need this to account for the fixed navbar though, otherwise the toolbar disappears behind it */

		      	// See https://www.froala.com/wysiwyg-editor/docs/server-integrations/php-image-upload
				imageUploadURL: '{{ route('ctrl::froala_upload') }}',
				imageUploadParams: {
					_token: '{{ csrf_token() }}',
					type: 'image'
				},
				fileUploadURL: '{{ route('ctrl::froala_upload') }}',
				fileUploadParams: {
					_token: '{{ csrf_token() }}',
					type: 'file'
				},
				events: {
					'image.error': function (error, response) {
						// use "this" for the editor instance.
						alert(JSON.parse(response).error);
						return false;
					}
				}
		      });
		  });
		</script>
@endpush


@if (empty($GLOBALS['push_froala_css']))
	@push('css')
		<!-- Include Editor style. -->
		<link href="{{ asset('assets/vendor/ctrl/vendor/froala3/css/froala_editor.pkgd.min.css') }}" rel="stylesheet" type="text/css" />
		<link href="{{ asset('assets/vendor/ctrl/vendor/froala3/css/froala_style.min.css') }}" rel="stylesheet" type="text/css" />

		{{-- Does anyone use Code Mirror? It's the HTML/code view AFAIK. Drop it for now:
		<!-- Include Code Mirror style -->
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.3.0/codemirror.min.css">
		--}}

		<!-- Include Editor Plugins style. -->
		{{-- Drop all these for now as well:
		<link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/vendor/froala/css/plugins/char_counter.css') }}">
		<link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/vendor/froala/css/plugins/code_view.css') }}">
		<link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/vendor/froala/css/plugins/colors.css') }}">
		<link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/vendor/froala/css/plugins/emoticons.css') }}">
		<link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/vendor/froala/css/plugins/file.css') }}">
		<link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/vendor/froala/css/plugins/fullscreen.css') }}">
		<link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/vendor/froala/css/plugins/image.css') }}">
		<link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/vendor/froala/css/plugins/image_manager.css') }}">
		<link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/vendor/froala/css/plugins/line_breaker.css') }}">
		<link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/vendor/froala/css/plugins/quick_insert.css') }}">
		<link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/vendor/froala/css/plugins/table.css') }}">
		<link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/vendor/froala/css/plugins/video.css') }}">
		--}}
	@endpush
	<?php $GLOBALS['push_froala_css'] = true; ?>
@endif

