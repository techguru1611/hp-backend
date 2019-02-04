<div> 
Hi {{$customerName}},<br />
{{ trans('apimessages.KYC_DOCUMENT_REJECTED_EMAIL_SUBJECT') }}<br />
<strong>{{ Config::get('constant.APP_NAME') }} team Comments:</strong> {{ $comments }} <br />
Thank you for using {{ Config::get('constant.APP_NAME') }}.<br /><br />

Thanks,<br />
{{ Config::get('constant.APP_NAME') }} Team
</div>