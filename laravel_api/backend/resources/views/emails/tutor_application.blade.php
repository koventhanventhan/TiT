<x-mail::message>
# New Tutor Application Received

A new tutor application has been submitted through the TiT Online Education platform.

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
{{ config('app.name') }}
</x-mail::message>
