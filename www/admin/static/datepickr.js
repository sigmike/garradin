/*
	datepickr - pick your date not your nose
	Copyright (c) 2010 josh.salverda - 2012 bohwaz Apache License 2.0
	https://code.google.com/p/datepickr/
	http://dev.kd2.org/garradin/
*/

function datepickr(targetElement, userConfig) {

	var config = {
		fullCurrentMonth: true,
		dateFormat: 'F jS, Y',
		firstDayOfWeek: 1,
		weekdays: ['Sun', 'Mon', 'Tues', 'Wednes', 'Thurs', 'Fri', 'Satur'],
		months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
		suffix: { 1: 'st', 2: 'nd', 3: 'rd', 21: 'st', 22: 'nd', 23: 'rd', 31: 'st' },
		defaultSuffix: 'th'
	},
	currentDate = new Date(),
	currentPosition = new Array(0,0),
	currentMaxRows = 4,
	// shortcuts to get date info
	get = {
		current: {
			year: function() {
				return currentDate.getFullYear();
			},
			month: {
				integer: function() {
					return currentDate.getMonth();
				},
				string: function(full) {
					var date = currentDate.getMonth();
					return monthToStr(date, full);
				}
			},
			day: function() {
				return currentDate.getDate();
			}
		},
		month: {
			integer: function() {
				return currentMonthView;
			},
			string: function(full) {
				var date = currentMonthView;
				return monthToStr(date, full);
			},
			numDays: function() {
				// checks to see if february is a leap year otherwise return the respective # of days
				return (get.month.integer() == 1 && !(currentYearView & 3) && (currentYearView % 1e2 || !(currentYearView % 4e2))) ? 29 : daysInMonth[get.month.integer()];
			}
		}
	},
	// variables used throughout the class
	daysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31],
	element, container, body, month, prevMonth, nextMonth,
	currentYearView = get.current.year(),
	currentMonthView = get.current.month.integer(),
	i, x, buildCache = [];

	function build(nodeName, attributes, content) {
		var element;

		if(!(nodeName in buildCache)) {
			buildCache[nodeName] = document.createElement(nodeName);
		}

		element = buildCache[nodeName].cloneNode(false);

		if(attributes != null) {
			for(var attribute in attributes) {
				element[attribute] = attributes[attribute];
			}
		}

		if(content != null) {
			if(typeof(content) == 'object') {
				element.appendChild(content);
			} else {
				element.innerHTML = content;
			}
		}

		return element;
	}

	function monthToStr(date, full) {
		return ((full == true) ? config.months[date] : ((config.months[date].length > 3) ? config.months[date].substring(0, 3) : config.months[date]));
	}

	function formatDate(milliseconds) {
		var formattedDate = '',
		dateObj = new Date(milliseconds),
		format = {
			d: function() {
				var day = format.j();
				return (day < 10) ? '0' + day : day;
			},
			D: function() {
				return config.weekdays[format.w()].substring(0, 3);
			},
			j: function() {
				return dateObj.getDate();
			},
			l: function() {
				return config.weekdays[format.w()] + 'day';
			},
			S: function() {
				return config.suffix[format.j()] || config.defaultSuffix;
			},
			w: function() {
				return dateObj.getDay();
			},
			F: function() {
				return monthToStr(format.n(), true);
			},
			m: function() {
				var month = format.n() + 1;
				return (month < 10) ? '0' + month : month;
			},
			M: function() {
				return monthToStr(format.n(), false);
			},
			n: function() {
				return dateObj.getMonth();
			},
			Y: function() {
				return dateObj.getFullYear();
			},
			y: function() {
				return format.Y().substring(2, 4);
			}
		},
		formatPieces = config.dateFormat.split('');

		for(i = 0, x = formatPieces.length; i < x; i++) {
			formattedDate += format[formatPieces[i]] ? format[formatPieces[i]]() : formatPieces[i];
		}

		return formattedDate;
	}

	function handleMonthClick() {
		// if we go too far into the past
		if(currentMonthView < 0) {
			currentYearView--;

			// start our month count at 11 (11 = december)
			currentMonthView = 11;
		}

		// if we go too far into the future
		if(currentMonthView > 11) {
			currentYearView++;

			// restart our month count (0 = january)
			currentMonthView = 0;
		}

		month.innerHTML = get.month.string(config.fullCurrentMonth) + ' ' + currentYearView;

		// rebuild the calendar
		while(body.hasChildNodes()){
			body.removeChild(body.lastChild);
		}
		body.appendChild(buildCalendar());
		bindDayLinks();

		return false;
	}

	function bindMonthLinks() {
		prevMonth.onclick = function() {
			currentMonthView--;
			return handleMonthClick();
		}

		nextMonth.onclick = function() {
			currentMonthView++;
			return handleMonthClick();
		}
	}

	// our link binding function
	function bindDayLinks() {
		var days = body.getElementsByTagName('a');

		for(i = 0, x = days.length; i < x; i++) {
			days[i].onclick = function() {
				currentDate = new Date(currentYearView, currentMonthView, this.innerHTML);
				element.value = formatDate(currentDate.getTime());
				close();
				return false;
			}
		}
	}

	function buildWeekdays() {
		var html = document.createDocumentFragment();
		// write out the names of each week day
		for(i = 0, x = config.weekdays.length; i < x; i++) {
			html.appendChild(build('th', {}, config.weekdays[i].substring(0, 2)));
		}
		return html;
	}

	function buildCalendar() {
		// get the first day of the month we are currently viewing
		var firstOfMonth = new Date(currentYearView, currentMonthView, config.firstDayOfWeek).getDay(),
		// get the total number of days in the month we are currently viewing
		numDays = get.month.numDays(),
		// declare our day counter
		dayCount = 0,
		weekCount = 0,
		html = document.createDocumentFragment(),
		row = build('tr');

		// print out previous month's "days"
		for(i = 1; i <= firstOfMonth; i++) {
			row.appendChild(build('td', {}, ''));
			dayCount++;
		}

		for(i = 1; i <= numDays; i++) {
			// if we have reached the end of a week, wrap to the next line
			if(dayCount == 7) {
				html.appendChild(row);
				row = build('tr');
				dayCount = 0;
				weekCount++;
			}

			// output the text that goes inside each td
			// if the day is the current day, add a class of "today"
			var today = (i == get.current.day() && currentMonthView == get.current.month.integer() && currentYearView == get.current.year());
			if (today)
			{
				currentPosition = [weekCount+1, dayCount];
			}
			row.appendChild(build('td', { className: today ? 'today' : '' }, build('a', { href: 'javascript:void(0)' }, i)));
			dayCount++;
		}

		// if we haven't finished at the end of the week, start writing out the "days" for the next month
		for(i = 1; i <= (7 - dayCount); i++) {
			row.appendChild(build('td', {}, ''));
		}

		html.appendChild(row);

		currentMaxRows = weekCount+1;

		return html;
	}

	function open() {
		document.onmousedown = function(e) {
			e = e || window.event;
			var target = e.target || e.srcElement;

			var parentNode = target.parentNode;
			if(target != element && parentNode != container) {
				while(parentNode != container) {
					parentNode = parentNode.parentNode;
					if(parentNode == null) {
						close();
						break;
					}
				}
			}

			e.preventDefault();
		}

		document.onkeypress = function(e) {
			var k = e.keyCode || e.which;

			if (k == 33) // PgUp
			{
				e.preventDefault();
				currentMonthView--;
				return handleMonthClick();
			}
			else if (k == 34) // PgDn
			{
				e.preventDefault();
				currentMonthView++;
				return handleMonthClick();
			}
			else if (k >= 37 && k <= 40) // Arrows
			{
				e.preventDefault();
				var pos = currentPosition.slice();
				if (k == 37) { // left
					if (pos[1] == 0) return;
					pos[1]--;
				}
				else if (k == 38) { // up
					if (pos[0] <= 1) return;
					pos[0]--;
				}
				else if (k == 39) { // right
					if (pos[1] == 6) return;
					pos[1]++;
				}
				else { // down
					if (pos[0] == currentMaxRows) return;
					pos[0]++;
				}

				var table = container.getElementsByTagName('table')[0];
				var row = table.getElementsByTagName('td')[pos[0]*7+pos[1]-7];

				if (row.innerHTML == "") return;

				table.getElementsByTagName('td')[currentPosition[0]*7+currentPosition[1]-7].className = '';
				row.className = 'today';

				currentPosition = pos;
				currentDate = new Date(currentYearView, currentMonthView, row.firstChild.innerHTML);
			}
			else if (k == 13 || k == 32)
			{
				element.value = formatDate(currentDate.getTime());
				close();
				e.preventDefault();
				return false;
			}
		}

		handleMonthClick();
		container.style.display = 'block';
	}

	function close() {
		document.onmousedown = null;
		container.style.display = 'none';
	}

	function initialise(userConfig) {
		if(userConfig) {
			for(var key in userConfig) {
				if(config.hasOwnProperty(key)) {
					config[key] = userConfig[key];
				}
			}
		}

		if (element.value)
		{
			var d = element.value.split('-');
			currentDate = new Date(parseInt(d[0], 10), parseInt(d[1], 10) - 1, parseInt(d[2], 10), 0, 0, 0, 0);
			currentYearView = get.current.year();
			currentMonthView = get.current.month.integer();
		}

		var inputLeft = inputTop = 0,
		obj = element;
		if(obj.offsetParent) {
			do {
				inputLeft += obj.offsetLeft;
				inputTop += obj.offsetTop;
			} while (obj = obj.offsetParent);
		}

		container = build('div', { className: 'calendar' });
		container.style.cssText = 'display: none; position: absolute; top: ' + (inputTop + element.offsetHeight) + 'px; left: ' + inputLeft + 'px; z-index: 9999;';

		var months = build('div', { className: 'months' });
		prevMonth = build('span', { className: 'prev-month' }, build('a', { href: '#' }, '&lt;'));
		nextMonth = build('span', { className: 'next-month' }, build('a', { href: '#' }, '&gt;'));
		month = build('span', { className: 'current-month' }, get.month.string(config.fullCurrentMonth) + ' ' + currentYearView);

		months.appendChild(prevMonth);
		months.appendChild(nextMonth);
		months.appendChild(month);

		var calendar = build('table', {}, build('thead', {}, build('tr', { className: 'weekdays' }, buildWeekdays())));
		body = build('tbody', {}, buildCalendar());

		calendar.appendChild(body);

		container.appendChild(months);
		container.appendChild(calendar);

		document.body.appendChild(container);
		bindMonthLinks();

		element.onfocus = open;
		element.onblur = close;
	}

	return (function() {
		element = typeof(targetElement) == 'string' ? document.getElementById(targetElement) : targetElement;
		initialise(userConfig);
	})();
}

// Add-on for HTML5 input type="date" fallback

(function() {
	var config_fr = {
		fullCurrentMonth: true,
		dateFormat: 'Y-m-d',
		firstDayOfWeek: 0,
		weekdays: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
		months: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
		suffix: { 1: 'er' },
		defaultSuffix: ''
	};

	function dateInputFallback()
	{
		var inputs = document.getElementsByTagName('input');
		var length = inputs.length;
		var enabled = false;

		for (i = 0; i < inputs.length; i++)
		{
			if (inputs[i].getAttribute('type') == 'date' && (inputs[i].type == 'text' || window.webkitConvertPointFromNodeToPage))
			{
				inputs[i].setAttribute('type', 'text');
				new datepickr(inputs[i], config_fr);
				inputs[i].setAttribute('readonly', 'readonly');
				enabled = true;
			}
		}

		if (enabled)
		{
			var scripts = document.head.getElementsByTagName('script');
			var www_url = scripts[scripts.length - 1].src.replace(/\/[^\/]+$/, '/');

			var link = document.createElement('link');
			link.type = 'text/css';
			link.rel = 'stylesheet';
			link.href = www_url + 'datepickr.css';

			document.head.appendChild(link);
		}
	}

	if (document.addEventListener)
	{
		document.addEventListener("DOMContentLoaded", dateInputFallback, false);
	}
	else
	{
		document.attachEvent("onDOMContentLoaded", dateInputFallback);
	}
} () );
