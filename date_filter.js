$(function() {
  /*var oTable = $('.DataTable').DataTable({
    "oLanguage": {
      "sSearch": "Filter Data"
    },
    "iDisplayLength": -1,
    "sPaginationType": "full_numbers",

  });*/




  /*$("#datepicker_from").datepicker({
    showOn: "button",
    buttonImage: "images/calendar.gif",
    buttonImageOnly: false,
    "onSelect": function(date) {
      minDateFilter = new Date(date).getTime();
      table.draw();
    }
  }).keyup(function() {
    minDateFilter = new Date(this.value).getTime();
    table.draw();
  });

  $("#datepicker_to").datepicker({
    showOn: "button",
    buttonImage: "images/calendar.gif",
    buttonImageOnly: false,
    "onSelect": function(date) {
      maxDateFilter = new Date(date).getTime();
      table.draw();
    }
  }).keyup(function() {
    maxDateFilter = new Date(this.value).getTime();
    table.draw();
  });*/

});

/*
// Date range filter
minDateFilter = "";
maxDateFilter = "";

$.fn.dataTableExt.afnFiltering.push(
  function(oSettings, aData, iDataIndex) {
    if (typeof aData._date == 'undefined') {
      aData._date = new Date(aData[1]).getTime();
    }

    if (minDateFilter && !isNaN(minDateFilter)) {
      if (aData._date < minDateFilter) {
        return false;
      }
    }

    if (maxDateFilter && !isNaN(maxDateFilter)) {
      if (aData._date > maxDateFilter) {
        return false;
      }
    }

    return true;
  }
);*/

/* Custom filtering function which will search data in column four between two values */
$.fn.dataTable.ext.search.push(
    function( settings, data, dataIndex ) {
        /*var min = parseInt( $('#datepicker_from').val(), 10 );
        var max = parseInt( $('#datepicker_to').val(), 10 );
        var age = parseFloat( data[1] ) || 0; // use data for the date column
		//alert('data[1]: ' + data[1]);
		timestamp = data[1].substr(0, data[1].indexOf('T'));
		alert('timestamp: ' + timestamp);
        if ( ( isNaN( min ) && isNaN( max ) ) || ( isNaN( min ) && age <= max ) || ( min <= age   && isNaN( max ) ) || ( min <= age   && age <= max ) ) {
            return true;
        }
        return false;*/
		
		

		//timestamp = data[1].substr(29, data[1].indexOf('</span>') - 29);
		timestamp = data[1].substr(0, data[1].indexOf('T'));
		//alert('timestamp, from_timestamp, to_timestamp, value.innerHTML in filter_by_date: ' + timestamp + ', ' + from_timestamp + ', ' + to_timestamp + ', ' + value.innerHTML);
		if(timestamp >= from_timestamp && timestamp <= to_timestamp) {
			return true;
		} else {
			return false;
		}

		
    }
);
 
$(document).ready(function() {
    //var table = $('#example').DataTable();
     
    // Event listener to the two range filtering inputs to redraw on input
    $('#datepicker_from, #datepicker_to').keyup( function() {
        table.draw();
    } );
} );

from_timestamp = 0; // 1970
d = new Date();
to_timestamp = (d.getTime() / 1000) + (10 * 365.25 * 86400); // 10 years from now
//alert('initialized from_timestamp, to_timestamp: ' + from_timestamp + ', ' + to_timestamp);
empirical_day_constant = 0.54220; // not sure what's going on with this; some shenanigans; leap seconds etc. maybe
//empirical_day_constant2 = 0.54220; // again, not sure why such a constant is needed
//$(document).ready(function() {
//	from_date_update();
//	to_date_update();
//});

function from_date_update() {
	from_date = $('#datepicker_from').val();
	from_date_length = from_date.length;
	//alert('from_date_length is: ' + from_date_length);
	if(from_date_length === 0) { // reset
		from_timestamp = 0; // 1970
//		filter_by_date();
	} else if(from_date_length === 4 && from_date.split('-').length - 1 === 0) { // add a dash
		from_timestamp = (from_date - 1970) * 365.25 * 86400;
//		filter_by_date();
		$('#datepicker_from').val(from_date + '-');
	} else if(from_date_length === 5) {
		from_timestamp = (from_date.substr(0, 4) - 1970) * 365.25 * 86400;
//		filter_by_date();
	} else if(from_date_length === 7 && from_date.split('-').length - 1 === 1) { // add a dash
		year = from_date.substr(0, 4);
		month = from_date.substr(5, 2);
		from_timestamp = ((year - 1970) * 365.25 * 86400) + get_month_seconds(year, month);
//		filter_by_date();
		$('#datepicker_from').val(from_date + '-');
	} else if(from_date_length === 8) {
		year = from_date.substr(0, 4);
		month = from_date.substr(5, 2);
		from_timestamp = ((year - 1970) * 365.25 * 86400) + get_month_seconds(year, month);
//		filter_by_date();
	} else if(from_date_length === 10 && from_date.split(' ').length - 1 === 0) { // add a space
		year = from_date.substr(0, 4);
		month = from_date.substr(5, 2);
		day = from_date.substr(8, 2);
		from_timestamp = ((year - 1970) * 365.25 * 86400) + get_month_seconds(year, month) + ((day - empirical_day_constant) * 86400);
//		filter_by_date();
		$('#datepicker_from').val(from_date + ' ');
	} else if(from_date_length === 11) {
		year = from_date.substr(0, 4);
		month = from_date.substr(5, 2);
		day = from_date.substr(8, 2);
		from_timestamp = ((year - 1970) * 365.25 * 86400) + get_month_seconds(year, month) + ((day - empirical_day_constant) * 86400);
//		filter_by_date();
	} else if(from_date_length === 13 && from_date.split(':').length - 1 === 0) { // add a colon
		year = from_date.substr(0, 4);
		month = from_date.substr(5, 2);
		day = from_date.substr(8, 2);
		hour = from_date.substr(11, 2);
		from_timestamp = ((year - 1970) * 365.25 * 86400) + get_month_seconds(year, month) + ((day - empirical_day_constant) * 86400) + (hour * 3600);
//		filter_by_date();
		$('#datepicker_from').val(from_date + ':');
	} else if(from_date_length === 14) { // add a colon
		year = from_date.substr(0, 4);
		month = from_date.substr(5, 2);
		day = from_date.substr(8, 2);
		hour = from_date.substr(11, 2);
		from_timestamp = ((year - 1970) * 365.25 * 86400) + get_month_seconds(year, month) + ((day - empirical_day_constant) * 86400) + (hour * 3600);
//		filter_by_date();
	} else if(from_date_length === 16) { // filter using a full precision date
		year = from_date.substr(0, 4);
		month = from_date.substr(5, 2);
		day = from_date.substr(8, 2);
		hour = from_date.substr(11, 2);
		minute = from_date.substr(14, 2);
		from_timestamp = ((year - 1970) * 365.25 * 86400) + get_month_seconds(year, month) + ((day - empirical_day_constant) * 86400) + (hour * 3600) + (minute * 60);
//		filter_by_date();
	}
}

function to_date_update() {
	to_date = $('#datepicker_to').val();
	to_date_length = to_date.length;
	//alert('to_date, to_date_length: ' + to_date + ', ' + to_date_length);
	if(to_date_length === 0) { // reset
		d = new Date();
		to_timestamp = (d.getTime() / 1000) + (10 * 365.25 * 86400); // 10 years from now
//		filter_by_date();
	} else if(to_date_length === 4 && to_date.split('-').length - 1 === 0) { // add a dash
		to_timestamp = (to_date - 1970 + 1) * 365.25 * 86400;
//		filter_by_date();
		$('#datepicker_to').val(to_date + '-');
	} else if(to_date_length === 5) {
		to_timestamp = (to_date.substr(0, 4) - 1970 + 1) * 365.25 * 86400;
//		filter_by_date();
	} else if(to_date_length === 7 && to_date.split('-').length - 1 === 1) { // add a dash
		year = to_date.substr(0, 4);
		month = to_date.substr(5, 2);
		to_timestamp = ((year - 1970) * 365.25 * 86400) + get_month_seconds(year, month) + ((31 - empirical_day_constant + 1) * 86400);
//		filter_by_date();
		$('#datepicker_to').val(to_date + '-');
	} else if(to_date_length === 8) {
		year = to_date.substr(0, 4);
		month = to_date.substr(5, 2);
		to_timestamp = ((year - 1970) * 365.25 * 86400) + get_month_seconds(year, month) + ((31 - empirical_day_constant + 1) * 86400);
//		filter_by_date();
	} else if(to_date_length === 10 && to_date.split(' ').length - 1 === 0) { // add a space
		year = to_date.substr(0, 4);
		month = to_date.substr(5, 2);
		day = to_date.substr(8, 2);
		to_timestamp = ((year - 1970) * 365.25 * 86400) + get_month_seconds(year, month) + ((day - empirical_day_constant + 1) * 86400);
//		filter_by_date();
		$('#datepicker_to').val(to_date + ' ');
	} else if(to_date_length === 11) {
		year = to_date.substr(0, 4);
		month = to_date.substr(5, 2);
		day = to_date.substr(8, 2);
		to_timestamp = ((year - 1970) * 365.25 * 86400) + get_month_seconds(year, month) + ((day - empirical_day_constant + 1) * 86400);
//		filter_by_date();
	} else if(to_date_length === 13 && to_date.split(':').length - 1 === 0) { // add a colon
		year = to_date.substr(0, 4);
		month = to_date.substr(5, 2);
		day = to_date.substr(8, 2);
		hour = to_date.substr(11, 2);
		to_timestamp = ((year - 1970) * 365.25 * 86400) + get_month_seconds(year, month) + ((day - empirical_day_constant) * 86400) + (hour * 3600);
//		filter_by_date();
		$('#datepicker_to').val(to_date + ':');
	} else if(to_date_length === 14) {
		year = to_date.substr(0, 4);
		month = to_date.substr(5, 2);
		day = to_date.substr(8, 2);
		hour = to_date.substr(11, 2);
		to_timestamp = ((year - 1970) * 365.25 * 86400) + get_month_seconds(year, month) + ((day - empirical_day_constant) * 86400) + (hour * 3600);
//		filter_by_date();
	} else if(to_date_length === 16) { // filter using a full precision date
		year = to_date.substr(0, 4);
		month = to_date.substr(5, 2);
		day = to_date.substr(8, 2);
		hour = to_date.substr(11, 2);
		minute = to_date.substr(14, 2);
		to_timestamp = ((year - 1970) * 365.25 * 86400) + get_month_seconds(year, month) + ((day - empirical_day_constant) * 86400) + (hour * 3600) + ((minute + 1) * 60);
//		filter_by_date();
	}
}

function get_month_seconds(year, month) {
	month_seconds = 0;
	// 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31
	if(month > 1) { // january
		month_seconds += 31 * 86400;
	}
	if(month > 2) { // february
		if(year % 4 == 0) { // leap year
			month_seconds += 29 * 86400;
		} else {
			month_seconds += 28 * 86400;
		}
	}
	if(month > 3) { // march
		month_seconds += 31 * 86400;
	}
	if(month > 4) { // april
		month_seconds += 30 * 86400;
	}
	if(month > 5) { // may
		month_seconds += 31 * 86400;
	}
	if(month > 6) { // june
		month_seconds += 30 * 86400;
	}
	if(month > 7) { // july
		month_seconds += 31 * 86400;
	}
	if(month > 8) { // august
		month_seconds += 31 * 86400;
	}
	if(month > 9) { // september
		month_seconds += 30 * 86400;
	}
	if(month > 10) { // october
		month_seconds += 31 * 86400;
	}
	if(month > 11) { // november
		month_seconds += 30 * 86400;
	}
	return month_seconds;
}