<x-layouts.email
    subject="{{ $subject }}"
    title="{{ $title }}"
    :subtitle="$subtitle ?? null"
    :action-url="$actionUrl ?? null"
    :action-text="$actionText ?? null"
    :otp="$otp ?? null"
    :notice="$notice ?? null"
>
    {!! $body !!}
</x-layouts.email>
