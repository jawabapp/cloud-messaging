@extends('cloud-messaging::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col text-left">
                    <h5>Report Cloud Messaging</h5>
                </div>
                <div class="col text-right">
                    <a href="{{ route('jawab.notifications.index') }}" class='btn btn-outline-primary btn-sm'>Cloud Messaging</a>
                    <a href="{{ route('jawab.notifications.compose') }}" class='btn btn-primary btn-sm text-white'>Compose notification</a>
                </div>
            </div>
        </div>

        <div class="card-body">
            @if($data)
                <table class="table table-striped table-bordered">
                    <tbody>
                    <tr>
                        <th scope="col" class="col-md-1">id</th>
                        <th scope="col" class="col-md-4">Info</th>
                        <th scope="col" class="col-md-2">Created</th>
                        <th scope="col" class="col-md-1">Audience</th>
                        <th scope="col" class="col-md-2">Sends</th>
                        <th scope="col" class="col-md-2">Opens</th>
                        <th scope="col" class="col-md-2">Conversions</th>
                    </tr>
                    @foreach($data as $item)
                    <tr>
                        <th scope="row">{{ $item['id'] }}</th>
                        <td>
                            <div>Sent By: {{ $item['sent_by'] }}</div>
                            <div>Title: {{ $item['title'] }}</div>
                            <div>Text: {!! wordwrap($item['text'], 100, "<br>\n") !!}</div>
                            <div>Target: {{ $item['target'] }}</div>
                        </td>
                        <td>{{ $item['created'] }}</td>
                        <td>{{ $item['audience'] }}</td>
                        <td>{{ $item['sends'] }}</td>
                        <td>{{ $item['opens'] }}</td>
                        <td>{{ $item['conversions'] }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <div class="alert alert-danger" role="alert">There are no Data</div>
            @endif
        </div>

        @if(!empty($cohort))
            <div class="card-header">
                <div class="row">
                    <div class="col text-left">
                        <h5>Cohort Report</h5>
                    </div>
                    <div class="col text-right">
                        <a href="{{ route('jawab.notifications.download-cohort') }}" class="btn btn-outline-primary btn-sm">download-cohort</a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                @if($cohort)
                    <table class="table table-striped table-bordered">
                        <tbody>
                        <tr>
                            <th scope="col" class="col-md-2">Notification Created</th>
                            <th scope="col" class="col-md-2">Counts</th>
                            <th scope="col" class="col-md-2">Audience</th>
                            <th scope="col" class="col-md-2">Sends</th>
                            <th scope="col" class="col-md-2">Opens</th>
                            <th scope="col" class="col-md-2">Conversions</th>
                        </tr>
                        @foreach($cohort as $key => $value)
                            <tr>
                                <th scope="row">{{ $key }}</th>
                                <td>{{ $value['counts'] }}</td>
                                <td>{{ $value['audience'] }}</td>
                                <td>{{ $value['sends'] }}</td>
                                <td>{{ $value['opens'] }}</td>
                                <td>{{ $value['conversions'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="alert alert-danger" role="alert">There are no Data</div>
                @endif
            </div>
        @endif
    </div>
@endsection
