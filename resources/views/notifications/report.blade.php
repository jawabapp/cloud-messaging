@extends('jawab-fcm::layouts.app')

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
                    <a href="{{ route('jawab.notifications.report.refresh') }}" class='btn btn-danger btn-sm'>Refresh $</a>
                </div>
            </div>
        </div>

        <div class="card-body">
            @if($data)
                <table class="table table-striped table-bordered">
                    <tbody>
                    <tr>
                        <th scope="col">id</th>
                        <th scope="col" class="col-md-4">Info</th>
                        <th scope="col" class="col-md-1">Created</th>
                        <th scope="col" class="col-md-1">Audience</th>
                        <th scope="col" class="col-md-2">Sent/Received/Open</th>
                        <th scope="col" class="col-md-1">Post/Campaign Viewed</th>
                        <th scope="col" class="col-md-1">Post/Campaign Vote</th>
                        <th scope="col" class="col-md-1">Post/Campaign Comments</th>
                    </tr>
                    @foreach($data as $item)
                    <tr>
                        <th scope="row">{{ $item['id'] }}</th>
                        <td>
                            <div>Sent By: {{ $item['sent_by'] }}</div>
                            <div>Title: {{ $item['title'] }}</div>
                            <div>Text: {!! wordwrap($item['text'], 100, "<br>\n") !!}</div>
                            <div>Target: {{ $item['target'] }}</div>
                            @if(!empty($item['campaign_type']))
                                <hr>
                                <div>{{ ucfirst($item['campaign_type']) }} Title: {!! wordwrap($item['campaign_title'], 100, "<br>\n") !!}</div>
                                <div>{{ ucfirst($item['campaign_type']) }} Created: {{ $item['campaign_created'] }}</div>
                                <div>{{ ucfirst($item['campaign_type']) }} Id: {{ $item['campaign_id'] }}</div>
                                <div>{{ ucfirst($item['campaign_type']) }} Link: {{ $item['campaign_link'] }}</div>
                            @endif
                        </td>
                        <td>{{ $item['created'] }}</td>
                        <td>{{ $item['tokens_count'] }}</td>
                        <td>{{ $item['fcm_sent_count'] }} / {{ $item['fcm_notification_received_count'] }} / {{ $item['fcm_notification_open_count'] }}</td>
                        <td>{{ $item['viewed'] }} / {{ $item['fcm_post_view_count'] }}</td>
                        <td>{{ intval($item['vote_up']) + intval($item['vote_down']) }} / {{ $item['fcm_post_vote_count'] }}</td>
                        <td>{{ $item['comments'] }} / {{ $item['fcm_post_comment_count'] }}</td>
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
                            <th scope="col" class="col-md-3">Sent/Received/Open</th>
                            <th scope="col" class="col-md-1">Post/Campaign Viewed</th>
                            <th scope="col" class="col-md-1">Post/Campaign Vote</th>
                            <th scope="col" class="col-md-1">Post/Campaign Comments</th>
                        </tr>
                        @foreach($cohort as $key => $value)
                            <tr>
                                <th scope="row">{{ $key }}</th>
                                <td>{{ $value['counts'] }}</td>
                                <td>{{ $value['tokens_count'] }}</td>
                                <td>{{ $value['fcm_sent_count'] }} / {{ $value['fcm_notification_received_count'] }} / {{ $value['fcm_notification_open_count'] }}</td>
                                <td>{{ $value['viewed'] }} / {{ $value['fcm_post_view_count'] }}</td>
                                <td>{{ $value['vote'] }} / {{ $value['fcm_post_vote_count'] }}</td>
                                <td>{{ $value['comments'] }} / {{ $value['fcm_post_comment_count'] }}</td>
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
