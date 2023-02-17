@extends('ctrl::form_fields.master')

@section('input')
	<div class="coordinates" id="coordinates-{{ $field['id'] }}">
		<i class="fa fa-map-marker" aria-hidden="true"></i>
	</div>	
	<input type="hidden" class="form-control" id="{{ $field['id'] }}" name="{{ $field['name'] }}" value="{{ $field['value'] }}" placeholder="">
@overwrite
{{-- Note that we need @overwrite because we include multiple instances of templates that extend form_fields.master: see https://github.com/laravel/framework/issues/1058 --}}

@push('js')
	@if (empty($field['readOnly']))
	<script>
		$(function () {
        	$('#coordinates-{{ $field['id'] }}').on('click', function(e) {
				/**
				Get the coordinates of the click event in pixels
				**/
				var x_coord = e.offsetX ;
				var y_coord = e.offsetY;
				/**
				Position the icon at this point (adjusted to account for the shape/size of the icon)
				**/
				$('#coordinates-{{ $field['id'] }} i').css({top: y_coord-19, left: x_coord-4});
				/**
				Convert this value to a relative percentage value to be saved to the database
				**/
				var x_percent = x_coord / $(this).width() * 100;
				var y_percent = y_coord / $(this).height() * 100;
				$('#{{ $field['id'] }}').val(Math.round(x_percent)+','+Math.round(y_percent));

				/**
				This is very MT-specific, but that's all we use Coordinates for anyway...
				**/
				if ($("#form_id_top").length) {
					$('#form_id_top').val(Math.round(y_percent));
				}
				if ($("#form_id_left").length) {
					$('#form_id_left').val(Math.round(x_percent));
				}
				
			});
		});		
	</script>
	@endif
@endpush

@push('css')
	<style>
		.coordinates {
			width: 400px;
			height: 220px;
			background-color: #ccc;
			background-size: contain;
			border: 1px solid #aaa;
			border-radius: 2px;
		}
		.coordinates i {
			position:relative;
			color: #fff;
		}
		@php
			/**
			Initialise the icon in the right place if necessary
			**/
			if ($field['value']) {
				$coordinates = explode(',', $field['value']);
				$x_percent = $coordinates[0] ?? null;
				$y_percent = $coordinates[1] ?? null;			
			}
		@endphp
		@if (!empty($x_percent) && !empty($y_percent))
		#coordinates-{{ $field['id'] }} i {
			left: {{ (($x_percent / 100) * 400) - 4 }}px;
			top: {{ (($y_percent / 100) * 220) - 19 }}px;
		}
		@endif

		@if ($background = app('\Phnxdgtl\Ctrl\Http\Controllers\CtrlController')->module->run('custom_coordinates_background',[
			'ctrl_class' => $ctrl_class,
			'object'     => $object
		]))
		#coordinates-{{ $field['id'] }} {
			background-image: url({{$background}});
		}
		@endif
	</style>
@endpush