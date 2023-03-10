@extends('ctrl::master')


@section('js')
<script src="{{ asset('assets/vendor/ctrl/vendor/jquery.form/jquery.form.js') }}"></script>
<script src="{{ asset('assets/vendor/ctrl/js/forms.js') }}"></script>

<script>
	{{-- Need to initalise the buttons here --}}
	$(function() {
		init_row_buttons();
	});
</script>
@stop

@section('css')
	<style>

	</style>
@stop

@section('content')

	<nav class="navbar navbar-default page-header">
	  <div class="container-fluid">
	    <!-- Brand and toggle get grouped for better mobile display -->
	    <div class="navbar-header">
	      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#list-options" aria-expanded="false">
	        <span class="sr-only">Toggle options</span>
	        <span class="icon-bar"></span>
	        <span class="icon-bar"></span>
	        <span class="icon-bar"></span>
	      </button>
	      <a class="navbar-brand">@if ($icon = $ctrl_class->get_icon())<i class="{{ $icon }} fa-fw"></i> @endif
	      	{{ $page_title }}</a>
	    </div>

	    <!-- Collect the nav links, forms, and other content for toggling -->
	    <div class="collapse navbar-collapse" id="list-options">
			@if ($page_description)
				<p class="navbar-text">{!! $page_description !!}</p>
			@endif
	      <ul class="nav navbar-nav navbar-right">
			<li><a href="{{ $back_link }}"><i class="fa fa-toggle-left"></i> Back</a></li>
			@if (!empty($delete_link) && false) {{-- We now include this in the buttons below; should use this instead? --}}
			<li><a href="#" rel="{{ $delete_link }}" class="delete-item"><i class="fa fa-trash"></i> Delete</a></li>
			@endif
	      </ul>
	    </div><!-- /.navbar-collapse -->
	  </div><!-- /.container-fluid -->
	</nav>

	<div id="ctrl-form">

	  <!-- Nav tabs -->
	  <ul class="nav nav-tabs" role="tablist">
	  	<?php $tab_loop = 0; // Hate mixing PHP and blade syntax; consider http://robin.radic.nl/blade-extensions/directives/assignment.html ? ?>
	  	@foreach ($tabbed_form_fields as $tab_name=>$tab_details)
	    <li role="presentation" @if ($tab_loop++ == 0)class="active" @endif><a href="#tab-{{ $tab_loop }}" aria-controls="tab-{{ $tab_loop }}" role="tab" data-toggle="tab"><i class="{{ $tab_details['icon'] }}"></i> {{ $tab_name }}</a></li>
	    @endforeach
	  </ul>

	  <form class="ajax" method="post" action="{{ $save_link }}">

	  		@foreach ($hidden_form_fields as $hidden_form_field)
	  			@include('ctrl::form_fields.hidden', ['field' => $hidden_form_field])
	  		@endforeach
		  <!-- Tab panes -->
		  <div class="tab-content">
		  	<?php $tab_loop = 0; // Hate mixing PHP and blade syntax; consider http://robin.radic.nl/blade-extensions/directives/assignment.html
		  	?>
		  	@foreach ($tabbed_form_fields as $tab_name=>$tab_details)
		    <div role="tabpanel" class="tab-pane fade in @if ($tab_loop++ == 0) active @endif" id="tab-{{ $tab_loop }}">

		    	@if ($tab_loop == 1)
					@include('ctrl::messages')
				@endif


				@if (!empty($tab_details['text']))
					{!! $tab_details['text'] !!}
				@endif

				@foreach ($tab_details['form_fields'] as $form_field)

					@include('ctrl::form_fields.'.$form_field['template'], ['field' => $form_field])

				@endforeach

			</div>
			@endforeach
		  </div>
		  <hr />
		  	<div class="row">
			  	<div class="col-md-6 col-xs-12">

						@if ($mode == 'view')
						<a class="btn btn-default" href="{{ $back_link }}"><i class="fa fa-arrow-circle-left"></i> Back</a>
						@else
			  			<a class="btn btn-default" href="{{ $back_link }}"><i class="fa fa-remove"></i> Cancel</a>
						@if(config('ctrl.save-and-continue',false))
						<button type="submit" value="continue" class="btn btn-default" data-loading-text="<i class='fa fa-circle-o-notch fa-spin fa-fw'></i> Saving..."><i class="fa fa-check-square"></i> Save and continue editing</button>
						@endif
						<button type="submit" value="save" class="btn btn-success" data-loading-text="<i class='fa fa-circle-o-notch fa-spin fa-fw'></i> Saving..."><i class="fa fa-check-square"></i> Save</button>
						@endif
				</div>
				<div class="col-md-6 col-xs-12">
					@if ($row_buttons)
					<div class="pull-right">
						{!! $row_buttons !!}
					</div>
					@endif
				</div>
			</div>
	  </form>

	</div>
	{{-- Now -- this is only used by Argos, but it might be handy for other projects, so leave it here as a constant element --}}
	<div id="preview-pane">
		<div class="error"><div class="alert alert-danger">An error has occurred</div></div>
		<div class="loading"><i class="fa fa-refresh fa-spin fa-3x fa-fw"></i></div>
		<div class="preview"></div>
	</div>


@stop
