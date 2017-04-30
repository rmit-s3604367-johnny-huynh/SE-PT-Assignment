@extends('layouts.dashboard')

@section('content')

<div class="dash__block">
	<h1 class="dash__header">Add Booking</h1>
	<h4 class="dash__description">Add a new booking to the system</h4>
	@if ($flash = session('message'))
		<div class="alert alert-success">
			{{ $flash }}
		</div>
	@endif
	@include('shared.error_message')
	<form class="request" method="POST" action="/admin/booking">
		{{ csrf_field() }}
		<div class="form-group request__flex-container">
			<div class="request__flex request__flex--left">
				<label for="input_month_year">Month & Year <span class="request__validate">(Select to go to month)</span></label>
			    <select name="month_year" id="input_month_year" class="form-control request__input" onchange="location = '/admin/booking/' + this.value">
			        @foreach ($months as $month)
			            <option value="{{ $month->format('m-Y') }}" {{ $date->format('m-Y') == $month->format('m-Y') ? 'selected' : null }}>{{ $month->format('F Y') }}</option>
			        @endforeach
			    </select>
			</div>
			<div class="request__flex request__flex--right">
				<label for="inputDay">Day <span class="request__validate"></span></label>
			    <select name="day" id="inputMonthYear" class="form-control request__input">
			        @for ($day = 1; $day <= $date->endOfMonth()->day; $day++)
			            <option value="{{ $day }}">{{ $day }}</option>
			        @endfor
			    </select>
			</div>
		</div>
		<div class="form-group">
			<label for="input_customer">Customer <span class="request__validate">(Full Name - ID)</span></label>
			<select name="customer_id" id="input_customer" class="form-control request__input">
				@foreach (App\Customer::all()->sortBy('lastname')->sortBy('firstname') as $customer)
					<option value="{{ $customer->id }}">{{ $customer->firstname . ' ' . $customer->lastname . ' - ' . $customer->id }}</option>
				@endforeach
			</select>
		</div>
		<div class="form-group">
			<label for="input_employee">Employee <span class="request__validate">(Title - Full Name - ID)</span></label>
			<select name="employee_id" id="input_employee" class="form-control request__input">
				@foreach (App\Employee::all()->sortBy('lastname')->sortBy('firstname')->sortBy('title') as $employee)
					<option value="{{ $employee->id }}">{{ $employee->title . ' - ' . $employee->firstname . ' ' . $employee->lastname . ' - ' . $employee->id }}</option>
				@endforeach
			</select>
		</div>
		<div class="form-group">
			<label for="input_activity">Activity <span class="request__validate">(Name - Duration)</span></label>
			<select name="activity_id" id="input_activity" class="form-control request__input">
				@foreach (App\Activity::all()->sortBy('name') as $activity)
					<option value="{{ $activity->id }}">{{ $activity->name . ' - ' . $activity->duration }}</option>
				@endforeach
			</select>
		</div>
		<div class="form-group">
			<label for="input_start_time">Start Time <span class="request__validate">(24 hour format)</span></label>
			<input name="start_time" type="time" id="input_start_time" class="form-control request__input" value="{{ old('start_time') ? old('start_time') : '09:00' }}" autofocus>
		</div>
		<button class="btn btn-lg btn-primary btn-block btn--margin-top">Add Booking</button>
	</form>
</div>
<hr>
<div class="dash__block" id="roster">
	<h1 class="dash__header dash__header--margin-top">Roster</h1>
	<h4 class="dash__description">Show the roster of a given month.</h4>
	<div class="form-group">
			<label for="input_month_year">Month & Year <span class="request__validate">(Select to go to month)</span></label>
		    <select name="month_year" id="input_month_year" class="form-control request__input" onchange="location = '/admin/booking/' + this.value + '#roster'">
		        @foreach ($months as $month)
		            <option value="{{ $month->format('m-Y') }}" {{ $date->format('m-Y') == $month->format('m-Y') ? 'selected' : null }}>{{ $month->format('F Y') }}</option>
		        @endforeach
		    </select>
	    </div>
	@include('shared.calender')
</div>
<hr>
<div class="dash__block" id="bookings">
	<h1 class="dash__header dash__header--margin-top">Bookings</h1>
	<h4 class="main_description">A table of all bookings on {{ $date->format('F Y') }}</h4>
	<div class="form-group">
		<label for="input_month_year">Month & Year <span class="request__validate">(Select to go to month)</span></label>
	    <select name="month_year" id="input_month_year" class="form-control request__input" onchange="location = '/admin/booking/' + this.value + '#bookings'">
	        @foreach ($months as $month)
	            <option value="{{ $month->format('m-Y') }}" {{ $date->format('m-Y') == $month->format('m-Y') ? 'selected' : null }}>{{ $month->format('F Y') }}</option>
	        @endforeach
	    </select>
    </div>
    @if ($bookings->count())
		<div class="table-responsive dash__table-wrapper">
		    <table class="table table--no-margin dash__table">
		        <tr>
					<th class="table--id table--right-solid">ID</th>
					<th class="table--name">Customer</th>
					<th class="table--name">Employee</th>
					<th class="table--name">Activity</th>
					<th class="table--time">Start Time</th>
					<th class="table--time">End Time</th>
					<th class="table--date">Date</th>
				</tr>
				@foreach ($bookings as $booking)
					<tr>
						<td class="table--id table--right-solid">{{ $booking->id }}</td>
						<td class="table--name table--right-dotted">{{ $booking->customer->firstname . ' ' . $booking->customer->lastname }}</td>
						@if ($booking->employee)
							<td class="table--name table--right-dotted">
								{{ $booking->employee->firstname . ' ' . $booking->employee->lastname }}
							</td>
						@else
							<td class="table--name table--right-dotted table--red">
								No Employee Selected
							</td>
						@endif
						<td class="table--name table--right-dotted">{{ $booking->activity->name }}</td>
						<td class="table--time table--right-dotted">{{ $booking->start_time }}</td>
						<td class="table--time table--right-dotted">{{ App\Booking::calcEndTime($booking->activity->duration, $booking->start_time) }}</td>
						<td class="table--date">{{ Carbon\Carbon::parse($booking->date)->format('d/m/y') }}</td>
					</tr>
				@endforeach
		    </table>
		</div>
	@else
		@include('shared.thumbs_down_error_message', [
			'message' => 'No bookings found.',
			'subMessage' => 'Try add an employee using the form above.'
		])
	@endif
</div>
@endsection