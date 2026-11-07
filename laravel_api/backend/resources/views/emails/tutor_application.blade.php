@php
    $tutorBrand = \App\Models\Institute::mailBrandName($data['institute_id'] ?? null);
@endphp
<x-mail::message>
# New Tutor Application Received

A new tutor application has been submitted through the {{ $tutorBrand }} platform.

**Applicant Details:**
- **Full Name:** {{ $data['fullName'] }}
- **Email:** {{ $data['email'] }}
- **Phone (WhatsApp):** {{ $data['phone'] }}
- **Subject(s):** {{ $data['subject'] }}
- **Medium:** {{ $data['medium'] }}

**Background:**
- **Highest Qualification:** {{ $data['qualification'] }}
- **Teaching Experience:** {{ $data['experience'] }} years

**Availability & Method:**
- **Availability:** {{ $data['availability'] }}

**Teaching Philosophy/Bio:**
{{ $data['bio'] }}

@if(isset($data['cvLink']) && $data['cvLink'])
**CV / Portfolio Link:** [View Document]({{ $data['cvLink'] }})
@endif

Thanks,<br>
{{ $tutorBrand }}
</x-mail::message>
