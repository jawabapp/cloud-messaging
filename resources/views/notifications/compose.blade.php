@extends('cloud-messaging::layouts.app')

@section('content')
    <form method="POST" action="{{ route('jawab.notifications.send') }}" enctype="multipart/form-data" id="compose_notifications_form" onsubmit="return confirm('Do you really want to send the notifications?');">
        {{ csrf_field() }}
        <div>
            <div class="card-header">
                <div class="row">
                    <div class="col text-left">
                        <h5>Compose Notification</h5>
                    </div>
                    <div class="col text-right">
                        <a href="{{ route('jawab.notifications.index') }}" class='btn btn-outline-primary btn-sm'>Cloud Messaging</a>
                    </div>
                </div>
            </div>
            <div>

                <div class="card mt-3">
                    <div class="card-header">
                        <span class="badge badge-secondary">1</span> Notification
                    </div>
                    <div class="card-body">
                        <jawab-notification-editor
                                title="{{ old('title', $notification->title ?? null) }}"
                                text="{{ old('text', $notification->text ?? null) }}"
                                error-image="{{ $errors->first('image') }}"
                                error-title="{{ $errors->first('title') }}"
                                error-text="{{ $errors->first('text') }}"
                        ></jawab-notification-editor>
                    </div>
                </div>

                @php
                    $config_extra_fields = config('cloud-messaging.extra_info', []);
                    $config_extra_fields = is_array($config_extra_fields) ? $config_extra_fields : [];

                    $old_extra_fields = old('extra_info', ($notification->extra_info ?? null));
                    $old_extra_fields = is_array($old_extra_fields) ? $old_extra_fields : [];

                    $extra_fields = array_merge(['name' => '', 'conversion' => ''], $config_extra_fields, $old_extra_fields);
                @endphp
                <div class="card mt-3">
                    <div class="card-header">
                        <span class="badge badge-secondary">2</span> Extra Fields
                    </div>
                    <div class="card-body">
                        <jawab-notification-extra-info
                                json-data='@json($extra_fields)'
                                json-errors='@json($errors->get('extra_info.*'))'
                        ></jawab-notification-extra-info>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <span class="badge badge-secondary">3</span> Target (audience)
                    </div>
                    <div class="card-body">
                        @if ($errors->has('target.*'))
                            <div class="alert alert-danger" role="alert">
                                @foreach ($errors->get('target.*') as $messages)
                                    @foreach ($messages as $message)
                                        <strong>{{ $message }}</strong>
                                    @endforeach
                                @endforeach
                            </div>
                        @endif
                        <div>
                            <jawab-target-editor
                                    target-audience-url="{{config('cloud-messaging.routes.target_audience')}}"
                                    filter-prefix-url="{{config('cloud-messaging.routes.filter_prefix')}}"
                                    :types="{{ json_encode(config('cloud-messaging.filter_types')) }}"
                                    :target-audience="{{ json_encode($notification->target ?? new stdClass) }}"
                            >
                            </jawab-target-editor>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <span class="badge badge-secondary">4</span> Scheduling
                    </div>
                    <div class="card-body">
                        <jawab-scheduling-editor
                                prop-schedule="{{ json_encode(old('schedule',$notification ? $notification->schedule : null)) }}"
                                now="{{ now()->format('Y-m-d H:i:s') }}"
                        ></jawab-scheduling-editor>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-row-reverse mt-3">
                <button type="submit" class="btn btn-primary">SEND</button>
            </div>
        </div>
    </form>
@endsection
