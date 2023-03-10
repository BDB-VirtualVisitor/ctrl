@extends('ctrl::master')


@section('css')
<!-- DataTables -->
{{-- <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/t/bs-3.3.6/dt-1.10.11,b-1.1.2,r-2.0.2/datatables.min.css"/> --}}
{{-- This should prevent bootstrap being loaded twice --}}
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/u/bs/dt-1.10.12,b-1.2.0,r-2.1.0,rr-1.1.2/datatables.min.css"/>


<!-- Row reorder -->
{{--  No longer required?
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/rowreorder/1.1.1/css/rowReorder.dataTables.min.css" />
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/rowreorder/1.1.1/css/rowReorder.bootstrap.min.css" />
--}}

<!-- WIP: might use this lightbox for opening images from table rows, and likely elsewhere. TBC -->
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/ekko-lightbox/4.0.1/ekko-lightbox.min.css" />

<style type="text/css">
 	/* Prevent button dropdowns from wrapping in table, see https://github.com/twbs/bootstrap/issues/9939 */
 	/* No longer needed:
	.btn-group.flex {
	  display: flex;
	}
	*/
	/* Not needed
	div.dataTables_wrapper div.dataTables_filter { /* Put the search box on the left * /
		text-align: left;
	}
	div.dataTables_wrapper div.dataTables_filter input {
		margin-left: 0; /* Don't need the indent now that we're removing the label  * /
	}
	div.dataTables_wrapper div.dataTables_custom_buttons { /* Put the butons on the right * /
		float: right;
	}
	*/

	/* Is this actually necessary? It looks fine with the border:;
	table.dataTable.table-bordered>thead>tr>th.final_header {
		border-right: none;
	}

	table.dataTable.table-bordered>thead>tr>th.empty_header {
		border-left: none;
	}
	*/
	table.dataTable>tbody>tr>td, table.dataTable>thead>tr>th {
		vertical-align: middle;
	}
	/* WIP */
	.row.table-header {
		margin-left: 0;
		margin-right: 2px;
		padding-top: 10px;
		padding-bottom: 10px;
		color: #333;
	    background-color: #f5f5f5;
	    border: 1px solid #ddd;
	    border-bottom: 0px;
    	border-top-left-radius: 3px;
    	border-top-right-radius: 3px;
	}
	table.dataTable {
		margin-top: 0px !important;
	}
	/* Move the footer (with search tools) to the top of the table, from https://www.datatables.net/forums/discussion/20272/how-to-put-individual-column-filter-inputs-at-top-of-columns */
	/* Not needed either, we've moved the filters into the searchable headers
	table.dataTable tfoot {
		display: table-header-group;
	}
	table.dataTable tfoot th {
		font-weight: normal; /* Text inputs look weird in bold * /
	}
	*/
	/* Remove bold text from filter inputs when rendered in the table header */
	table.dataTable thead th input,
		table.dataTable thead th select {
		font-weight: normal;
	}
	/* A very minor one; this aligns the sort buttons vertically, now that the table header contains filter inputs and is therefore deeper */
	table.dataTable thead .sorting:after, table.dataTable thead .sorting_asc:after, table.dataTable thead .sorting_desc:after, table.dataTable thead .sorting_asc_disabled:after, table.dataTable thead .sorting_desc_disabled:after {
		bottom: 14px;
		right: 14px;
	}

	/* Format the "order" column to centre align values, looks neater. Also add a vertical "move" cursor */
	table.dataTable td.reorder {
		text-align: center;
		cursor: ns-resize;
	}

	/* Stretch the column search fields and dropdowns so that they're full-width */
	/** I don't think we should do this with select boxes...? Try this, but it may break some sites:
	table.dataTable thead th select.form-control, table.dataTable thead th .input-group {
	**/
	table.dataTable thead th .input-group {
		width: 90%;
	}
	 table.dataTable thead th .input-group span.input-group-addon:first-child {
	 	width: 1px; /* Otherwise the magnifying glass is stretched */
	 }
	table.dataTable thead th .input-group input.form-control {
		border-top-right-radius: 4px !important; /* Override a Bootstrap issue; because we add the "clear" option as an add-on, Bootstrap attempts to straighten the right edge */
		border-bottom-right-radius: 4px !important;
	}


	/* Add a "Clear search" button, from http://stackoverflow.com/questions/20062218/how-do-i-clear-a-search-box-with-an-x-in-bootstrap-3 */

	span.input-group-addon.clear-search {
		background-color: transparent;
		z-index: 100;
		border: none;
		position:absolute;
	    right:0px;
	    top:4px;
	    bottom:0;
	    height:14px;
	    font-size:14px;
	    cursor:pointer;
	    color:#aaa;
	}
	div.input-group-sm span.input-group-addon.clear-search {
		top:0px;
	}

	th:focus { /* It's possible to "select" the <th> for some reason, which is confusing */
		outline: none;
	}

	td div.row-buttons {
		white-space: nowrap; /* Don't wrap the add, delete, "more" buttons on each table row */
	}

	/* Allow us to use .text-danger on the "delete" link in a dropdown menu */
	/* No longer used
	.dropdown-menu>li>a.text-danger {
		color: #a94442;
	}
	*/
	/* Not used any more
	h1 small {
		/* Make the "subheading" a bit smaller than usual * /
	    font-size: 50%;
	}
	*/


    /*
    	This hacks in a solution to a possible bug in datatables, whereby the ordering icon vanishes from the "order" column when you order by another column.
    	There's an argument to say that we shouldn't allow any column ordering if we're allowing the table to be reordered, but... that's another discussion.
    */
    table.dataTable thead .sorting_asc_disabled {
    	padding-right: 30px;
    }
    table.dataTable thead .sorting_asc_disabled:after {
	    content: "\e150";
	    opacity: 0.2;
	    bottom: 14px;
    	right: 14px;
    	color: #333;
	}

	/* We display a Font Awesome spinner for the processing message, so adjust the width of the panel */
	div.dataTables_wrapper div.dataTables_processing {
		width: auto;
		margin-left: 0;
		padding: 0.5em 0;
	}

	#key {

	}

    <?php /* Not using this any more:

	/* Hacking in disabled tooltips, from http://jsfiddle.net/cSSUA/209/ and http://stackoverflow.com/questions/13311574/how-to-enable-bootstrap-tooltip-on-disabled-button * /
	.tooltip-wrapper {
	  display: inline-block; /* display: block works as well * /

	}

	.tooltip-wrapper .btn[disabled] {
	  /* don't let button block mouse events from reaching wrapper * /
	  pointer-events: none;
	}
	*/ ?>
</style>

@if (!empty($custom_css))
	<style>
		{!! $custom_css !!}
	</style>
@endif

@stop

@section('js')
<!-- DataTables -->
{{--
<script type="text/javascript" src="https://cdn.datatables.net/t/bs-3.3.6/dt-1.10.11,b-1.1.2,r-2.0.2/datatables.min.js"></script>
<!-- Row reorder -->
<script type="text/javascript" src="https://cdn.datatables.net/rowreorder/1.1.1/js/dataTables.rowReorder.min.js"></script>
--}}
{{-- This version should include row reorder as well --}}
<script type="text/javascript" src="https://cdn.datatables.net/u/bs/dt-1.10.12,b-1.2.0,r-2.1.0,rr-1.1.2/datatables.min.js"></script>
{{-- Pagination plugin --}}
<script type="text/javascript" src="https://cdn.datatables.net/plug-ins/1.10.12/pagination/select.js"></script>

<!-- WIP: might use this lightbox for opening images from table rows, and likely elsewhere. TBC -->
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/ekko-lightbox/4.0.1/ekko-lightbox.min.js"></script>


<script>


// Attempting to allow filters in column headers, which don't then sort the column when clicked
// See http://jsfiddle.net/s8F9V/1/ and https://www.datatables.net/forums/discussion/20272/how-to-put-individual-column-filter-inputs-at-top-of-columns
function stopPropagation(evt) {
	if (evt.stopPropagation !== undefined) {
		evt.stopPropagation();
	} else {
		evt.cancelBubble = true;
	}
}

$(function() {

	/**
	* New, Video modals, from https://stackoverflow.com/a/54481920/1463965
	**/
	$('body').on('click',".videoModal", function () {
		var theModal = $(this).data("target"),
			videoSRC = $(this).attr("data-video");
		$(theModal + ' source').attr('src', videoSRC);
		$(theModal + ' video').load();
		$(theModal + ' button[data-dismiss="modal"]').click(function () {
			$(theModal + ' video').get(0).pause();
			$(theModal + ' video').get(0).currentTime = 0;
		});
	});
	/* No longer necessary, we've removed the main search input
	$.extend($.fn.dataTableExt.oStdClasses, {
		"sFilterInput": "form-control", // Remove the default input-sm
		"sFilter": "input-group" // Add input-group class to the parent div
			// This won't work unless we can remove the parent <label>, which we now do (below)
	});
	*/

	// Add text search inputs to each column, from https://datatables.net/examples/api/multi_filter.html
    $('#data-table thead th').each( function () {
    	var column_searchable = $(this).attr('data-search-text');
        var column_title = $(this).text();
        if (column_searchable === 'true') {
        	if ($('#data-table thead th').length > 5) {
				/**
					If we have more than five columns, make them smaller for legibility.
					Also remove the "click to clear field" button, it just overlaps the text
					and is probably very rarely used anyway, and make the fields a bit wider
				**/
        		$(this).html('<div class="input-group input-group-sm" style="width: 95%"><input type="text" class="form-control" placeholder="'+column_title+'" onclick="stopPropagation(event);" /><!--<span class="input-group-addon clear-search" onclick="stopPropagation(event);"><i class="fa fa-remove"></i></span>--></div>');
        	}
        	else {
        		$(this).html('<div class="input-group"><span class="input-group-addon"><i class="fa fa-search"></i></span><input type="text" class="form-control" placeholder="'+column_title+'" onclick="stopPropagation(event);" /><span class="input-group-addon clear-search" onclick="stopPropagation(event);"><i class="fa fa-remove"></i></span></div>');
        	}
        }
    } );

    var table = $('#data-table').DataTable({

		stateSave: true, // From https://datatables.net/reference/option/stateSave, means we retain search terms and current page when returning to the table

		@if ($defaultOrder)
			"order": {!! $defaultOrder !!},
		@endif

		"sPaginationType": "simple_numbers", // use "listbox" for Argos-style dropdowns, but I'm going off these
		@if ($page_length === false)
        "paging": false,
       	@else ($page_length)
        "pageLength": {{ $page_length }},
        @endif

		"orderCellsTop": true, // Is this required? It's designed to prevent the click on a search box propagating to the reorder button, but I think we handle this using stopPropagation above
		dom: "<'row'<'col-sm-12'tr>>" +
			 "<'row'<'col-sm-5'i><'col-sm-7'p>>",
	 	"language": { // Customise the processing message
    		 "processing": '<i class="fa fa-cog fa-spin fa-3x fa-fw margin-bottom"></i>'
  		},
        processing: true,
        serverSide: true,
        ajax: '{!! route('ctrl::get_data',[$ctrl_class->id,$filter_string]) !!}',
        columns: {!! $js_columns !!},
        @if ($can_reorder)
        rowReorder: { update: false }, // Prevents the data from being redrawn after we've reordered; is this what we want? Depends if we get the Ajax saving sorted
        @endif
        drawCallback: function( settings ) {
        	$('.dropdown-toggle').dropdown(); // Refresh Bootstrap dropdowns
        	init_row_buttons();
    	},
    	/* No longer necessary, we've removed the main search input
    	language: { 'searchPlaceholder': 'Search...','sSearch':'' }, // Remove the "Search:" label, and add a placeholder
    	*/
    	initComplete: function () { // Add column filters; see https://datatables.net/examples/api/multi_filter_select.html
    		var total_columns = this.api().columns().indexes().length;
            this.api().columns().every( function () {
                var column = this;

                // Only draw a dropdown for fields marked as such (usually, relationship fields, possibly ENUM?)
                var column_searchable = $(column.header()).attr('data-search-dropdown');
                var column_title = $(column.header()).text();
                if (column_searchable !== 'true') return false; // Ignore columns not marked as searcahble

                if ($('#data-table thead th').length > 5) { // If we have more than five columns, make them smaller for legibility (as above)
                	select_html = '<select class="form-control input-sm" onclick="stopPropagation(event);" style="min-width: '+(column_title.length/2)+'em"><option value="">'+column_title+'</option></select>';
                }
                else {
                	select_html = '<select class="form-control" onclick="stopPropagation(event);" style="min-width: '+column_title.length+'em"><option value="">'+column_title+'</option></select>';
                }
                var select = $(select_html)
                    .appendTo( $(column.header()).empty() )
                    .on( 'change', function () {
                    	var val = $.fn.dataTable.util.escapeRegex(
                            $(this).val()
                        );
                        console.log(val);
                    	// Why regex these? Surely a dropdown will be a list of values that match records exactly?
                    	/* Regex version...
                        column
                            .search( val ? '^'+val+'$' : '', true, false )
                            .draw();
                           */
                         // Non reg-ex version
                        column
                            .search( val, false, false ) // input, regex, smart : see https://datatables.net/reference/api/column().search()
                            .draw();
                    } );
                /*
                	OK, this doesn't work, as it just populates the dropdown with all the UNIQUE values in the column for the given page of the table
                	So, if we have three "brands" listed on page one, but 40 brands in total (all shown on subsequent pages of the table), we end up
                	with only three in the dropdown.
                	Can we use console.log(column.dataSrc()); (eg, brand.title) to load these via Ajax...? I can't see any other way of doing it :-(
                */
                dropdown_approach = 'ajax'; // Could be 'unique', that's the old approach
                if (dropdown_approach == 'unique') {
	                column.data().unique().sort().each( function ( d, j ) {
	                	// We're going to assume that Yes/No values in this dropdown correspond to TINYINT values; is there a better way to check this?
	                	if (d == 'No') {
	                		v = 0;
	                	}
	                	else if (d == 'Yes') {
	                		v = 1;
	                	}
	                	else {
	                		v = d;
	                	}
	                    select.append( '<option value="'+v+'">'+d+'</option>' )
	                } );
	            }
	            else if (dropdown_approach == 'ajax') {
	                var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
	                var data_src = column.dataSrc();
					$.ajax({
					    url: '{!! route('ctrl::populate_datatables_dropdowns',[$ctrl_class->id,$filter_string]) !!}',
					    type: 'POST',
						data: {_token: CSRF_TOKEN, data_src: data_src},
					    dataType: 'JSON',
					    success: function (data) {
					        $.each(data.options, function(d, j) {
							    select.append( '<option value="'+d+'">'+j+'</option>' )
							});
					    }
					});
				}
            } );
		}
    });

    // Restore search state, from https://datatables.net/forums/discussion/33182/individual-column-searching-with-statesave-not-showing-previous-values
    var state = table.state.loaded();
    if (state) {
        table.columns().eq(0).each(function (colIdx) {
            var colSearch = state.columns[colIdx].search;
            if (colSearch.search) { // We're searching column colIdx for the term colSearch, so re-populate the search box
                $('input', table.column(colIdx).header()).val(colSearch.search);
            }
        });
        // This breaks the table as pagination isn't maintained
        // table.draw();
        // Adding 'page' seems to work, but is it even necessary?
        // table.draw('page');
    }


    // Apply the search (again, see https://datatables.net/examples/api/multi_filter.html)
    table.columns().every( function () {
        var that = this;

        $( 'input', this.header() ).on( 'keyup change', function () {
            if ( that.search() !== this.value ) {
                that
                    .search( this.value )
                    .draw();
            }
        } );

    } );

    // Re-order rows
   table.on('row-reorder', function (e, diff, edit) { // See https://datatables.net/reference/event/row-reorder
	   	var new_order = [];
        for ( var i=0, ien=diff.length ; i<ien ; i++ ) {
	        var row = $(diff[i].node);
	        var row_id = row.attr('id');
   			// var row_old_order = diff[i].oldPosition + 1; // Not useful
   			// console.log(diff[i].newPosition);
   			var row_new_order = diff[i].newPosition + 1;
	        new_order.push({
		        "id" : row_id,
		        "order" : row_new_order
		    });
	    }
	    // Now, send this result to a script that updates object orders, and it should all just work. Do we call draw() afterwards?
	    var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
		$.ajax({
		    url: '{!! route('ctrl::reorder_objects',array($ctrl_class->id)) !!}',
		    type: 'POST',
		    data: {_token: CSRF_TOKEN, new_order: new_order },
		    dataType: 'JSON',
		    success: function (data) {
		       $.notify({
					icon: 'fa fa-check-square-o fa-fw',
					message: 'Items reordered',
				},{
					type: "success",
					newest_on_top: true,
					delay: 2500,
				});
		    }
		});
    }); // .draw(); // Is the redraw actually necessary? NO, and it breaks stateSave...

   	// Allow the search field to be cleared:
   	$('span.clear-search').on('click',function() {
   		// console.log('clear');
   		$(this).prev('input').val('');
   		table.columns().search( '' ) // See https://www.datatables.net/plug-ins/api/fnFilterClear
 		.draw();
   	});

// If we're using the pagination plugin, style it with Bootstrap:
		   	$('.dataTables_paginate.paging_listbox').find('select').addClass('form-control');




    // Add custom buttons

    // $('div.dataTables_custom_buttons').html('<a href="#" class="btn btn-success"><i class="fa fa-plus"></i> Add</a>');

    /* Can we add this in the HTML instead? See below:
    $('div.dataTables_custom_buttons').html('<!-- Split button --><div class="btn-group"><a href="{{ route('ctrl::edit_object',$ctrl_class->id) }}" class="btn btn-success"><i class="fa fa-plus"></i> Add a {{ $ctrl_class->name }}</a></a><button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button><ul class="dropdown-menu dropdown-menu-right"><li><a href="#">Action</a></li></ul></div>');
    */

    // This removes the parent "label" from the search filter, from http://stackoverflow.com/questions/170004/how-to-remove-only-the-parent-element-and-not-its-child-elements-in-javascript
    /* No longer necessary, we've removed the main search input
   	var label = $('#data-table_filter label');
   	var contents = label.contents();
	label.replaceWith(contents);
	$('#data-table_filter').prepend('<span class="input-group-addon"><i class="fa fa-search"></i></span>');
	*/

});
</script>

@stop

@section('content')

	{{-- Is there any value in having a dashboard at all? Can't we just have a "Back to list" button when editing an item, I think that's all we'd use a breadcrumb for anyway... --}}
	{{-- Yes, agreed. We need a "Back to list" option when editing items OR viewing a filtered list. That's it.
	<ol class="breadcrumb">
	  <li><a href="{{ route('ctrl::dashboard') }}">Dashboard</a></li>
	  <li class="active">{{ $ctrl_class->name }}</li>
	</ol>
	--}}

<?php /* Old version, let's use a navbar instead now that we sometimes have buttons in the header
	<div class="page-header">

		<h1>@if ($icon = $ctrl_class->get_icon())<i class="{{ $icon }}"></i> @endif
		{{ ucwords($ctrl_class->get_plural()) }}
		@if ($filter_description)
			<small>Showing all {{ $ctrl_class->get_plural() }} {!! $filter_description !!}.</small>
		@endif

		<div class="pull-right">
			@if ($filter_description)
			<a href="{{ route('ctrl::list_objects',$ctrl_class->id) }}" class="btn btn-default">@if ($icon = $ctrl_class->get_icon())<i class="{{ $icon }}"></i> @endif Show all</a>
			@endif
			<a href="#TBC" class="btn btn-default"><i class="fa fa-toggle-left"></i> Back</a>
		</div>

		</h1>

	</div>
	*/ ?>

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
			{{ ucwords($ctrl_class->get_plural()) }}</a>
	    </div>

	    <!-- Collect the nav links, forms, and other content for toggling -->
	    <div class="collapse navbar-collapse" id="list-options">
			
			@if (!empty($page_description))
			<p class="navbar-text">{!! $page_description !!}</p>
			@endif

			@if ($key) {{-- No longer used, looks rubbish and needs a rethink --}}
			<a data-toggle="modal" data-target="#help" class="btn btn-default navbar-btn navbar-right"><i class="fa fa-question"></i> Help</a>
			@endif

	      <ul class="nav navbar-nav navbar-right">

			@if ($export_link)
				<li><a href="{{ $export_link }}"><i class="fa fa-download"></i> Export</a></li>
			@endif

			@if ($import_link)
				<li><a href="{{ $import_link }}"><i class="fa fa-upload"></i> Import</a></li>
			@endif

	  		@if ($show_all_link)
			<li><a href="{{ $show_all_link }}" _class="btn btn-default navbar-btn">@if ($icon = $ctrl_class->get_icon())<i class="{{ $icon }}"></i> @endif Show all</a></li>
			@endif

			@if ($unfiltered_list_link) {{-- Can we link "back" to an unfiltered list? --}}
				<li><a href="{{ $unfiltered_list_link }}" _class="btn btn-default navbar-btn"><i class="fa fa-toggle-left"></i> Back</a></li>
			@endif




			{{-- This may prove useful at some point?
	        <li class="dropdown">
	          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Dropdown <span class="caret"></span></a>
	          <ul class="dropdown-menu">
	            <li><a href="#">Action</a></li>
	            <li><a href="#">Another action</a></li>
	            <li><a href="#">Something else here</a></li>
	            <li role="separator" class="divider"></li>
	            <li><a href="#">Separated link</a></li>
	            <li role="separator" class="divider"></li>
	            <li><a href="#">One more separated link</a></li>
	          </ul>
	        </li>
	        --}}

	      </ul>

	    </div><!-- /.navbar-collapse -->
	  </div><!-- /.container-fluid -->
	</nav>


	<table class="table table-bordered table-striped" id="data-table">
        <thead>
            <tr>
            	{!! $th_columns !!}
                <th width="1"  data-orderable="false"  data-searchable="false">
				@if ($add_link)<a href="{{ $add_link }}" class="btn btn-success pull-right"><i class="fa fa-plus"></i> Add</a>@endif

                {{-- Or use a split button if necessary --}}
                {{-- <!-- Split button --><div class="btn-group flex"><a href="{{ route('ctrl::edit_object',$ctrl_class->id) }}" class="btn btn-success"><i class="fa fa-plus"></i> Add</a><button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button><ul class="dropdown-menu dropdown-menu-right"><li><a href="#">Action</a></li></ul></div>--}}
                </th>
            </tr>
        </thead>
    </table>

    <hr />
    	@if ($key)
		<div id="key">
    	{!! $key !!}
    	</div>
    @endif

	{{-- Purely for ShowPonyPrep --}}
	<div class="modal fade" id="videoModal" tabindex="-1" role="dialog" aria-labelledby="videoModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title" id="videoModalLabel">Video Preview</h4>
				</div>
				<div class="modal-body">
					<video controls width="100%">
						<source src="" type="video/mp4">
					</video>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
				</div>
			</div>
		</div>
	</div>

@stop

