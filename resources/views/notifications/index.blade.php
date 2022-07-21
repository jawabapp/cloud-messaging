@extends('cloud-messaging::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col text-left">
                    <h5>Cloud Messaging</h5>
                </div>
                <div class="col text-right">
                    <a href="{{ route('jawab.notifications.report') }}" class='btn btn-outline-primary btn-sm'>Report</a>
                    <a href="{{ route('jawab.notifications.compose') }}" class='btn btn-primary btn-sm text-white'>Compose notification</a>
                </div>
            </div>
        </div>
        <div class="card-body pb-0">
            <form>
                <div class="jumbotron m-0 p-4">
                    <div class="row">

                        @php
                            $config_extra_fields = config('cloud-messaging.extra_info', []);
                            $config_extra_fields = is_array($config_extra_fields) ? $config_extra_fields : [];

                            $old_extra_fields = old('extra_info', ($notification->extra_info ?? null));
                            $old_extra_fields = is_array($old_extra_fields) ? $old_extra_fields : [];

                            $extra_fields = array_merge(['name' => '', 'conversion' => ''], $config_extra_fields, $old_extra_fields);
                        @endphp

                        @foreach($extra_fields as $extra_field_name => $extra_field_value)
                            <div class="col-md-4">
                                <input type="text" class="form-control mb-2" name="fltr[{{ $extra_field_name }}]" value="{{ request('fltr')[$extra_field_name] ?? '' }}" placeholder="filter by {{ $extra_field_name }}">
                            </div>
                        @endforeach
                    </div>

                    <div class="row mt-2">
                        <div class="col-md-6 text-left">
                            <div class="py-2"><b>Count</b> : {{ $data->total() }}</div>
                        </div>
                        <div class="col-md-6 text-right">
                            <button class="btn btn-info"><i class="fa fa-search"></i> Search</button>
                            <a href="{{ url()->current() }}" class="btn btn-info"><i class="fa fa-refresh"></i> Reset</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body">
            @if($data->items())
                <table class="table table-striped table-bordered">
                    <tbody>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Name</th>
                        <th scope="col">Title</th>
                        <th scope="col">Text</th>
                        <th scope="col">Created at</th>
                        <th scope="col">Sent by</th>
                        <th scope="col">Scheduled</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-center">Actions</th>
                    </tr>
                    @foreach($data->items() as $item)
                    <tr>
                        <th scope="row">{{ $item->id }}</th>
                        <td>{{ $item->extra_info['name'] ?? '-' }}</td>
                        <td>{{ $item->title ?? '-' }}</td>
                        <td><p class="m-0">{!! nl2br($item->text) !!}</p></td>
                        <td>{{ Timezone::convertToLocal($item->created_at) }}</td>
                        <td>{{ $item->user->name ?? '' }}</td>
                        <td>
                            @php
                                $scheduleType = $item->schedule['type'] ?? 'Now';
                                $scheduleDate = $item->schedule['date'] ?? '';
                                $scheduleDate = $scheduleDate ? Timezone::convertToLocal(\Carbon\Carbon::parse($scheduleDate)) : '';
                            @endphp
                            {{ $scheduleType . ' ' . $scheduleDate }}
                        </td>
                        <td>{{ $item->status ?? 'completed' }}</td>
                        <td class="text-center d-flex flex-row border-0">
                            <a href="{{ route('jawab.notifications.compose', ['notification'=>$item->id]) }}" class='btn btn-info btn-sm'>Copy</a>
                            <a href="{{ route('jawab.notifications.show', [$item->id]) }}" class='ml-1 btn btn-warning btn-sm'>View</a>
                            @if($item->status === 'pending' && isset($item->schedule['type']) && $item->schedule['type'] === 'Scheduled')
                                <a href="{{ route('jawab.notifications.delete', [$item->id]) }}" class='ml-1 btn btn-danger btn-sm'>Delete</a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <div class="alert alert-danger" role="alert">There are no Data</div>
            @endif
        </div>
        <div class="card-footer">
            {{ $data->appends(request()->query())->links() }}
        </div>
    </div>
@endsection
