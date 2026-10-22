# Sri Lanka Tri-Lingual Language Review (Tamil & Sinhala)
**Document:** `docs/LANGUAGE_REVIEW.md`  
**Application:** Online Tuition SaaS & RAG Study Assistant  
**Target Reviewers:** Native Tamil (தமிழ்) and Sinhala (සිංහල) Educators and Translators  
**Scope:** All user-facing strings across student dashboards, public widgets, teacher materials, safeguarding responses, error states, and locked-study notices.

---

## Instructions for Native Reviewers
1. Verify grammatical accuracy, natural educational phrasing, and cultural appropriateness for Sri Lankan secondary students (G.C.E. O/L and A/L).
2. Ensure technical terminology (e.g., *RAG*, *OCR*, *AI*, *PDF*) is translated into natural, unambiguous regional terms without colloquial slang or untranslated jargon.
3. Check safeguarding messages for empathy and clear crisis helpline instructions.
4. Record any recommended corrections in the **Reviewer Notes / Corrections** column.

---

## 1. Safeguarding & Crisis Guardian Messages
*Triggered automatically when a student expresses personal distress, hopelessness, or self-harm intent.*

| Key / Context | English Meaning | Tamil (தமிழ்) | Sinhala (සිංහල) | Reviewer Notes |
|---|---|---|---|---|
| `distress.caring_message` | I am really sorry that you are feeling this way, but please know that you are not alone. Please reach out and talk to a trusted adult or your teacher right now. Support is always available to help you through this. | நீங்கள் இவ்வாறு உணர்வது மிகுந்த வருத்தமளிக்கிறது, ஆனால் நீங்கள் தனிமையில் இல்லை என்பதை தயவுசெய்து நினைவில் கொள்ளுங்கள். உடனடியாக உங்கள் ஆசிரியர் அல்லது நீங்கள் நம்பும் ஒரு பெரியவரிடம் பேசுங்கள். உங்களுக்கு உதவ எப்போதும் ஆதரவு தயாராக இருக்கிறது. | ඔබ මේ ආකාරයෙන් හැඟෙන බව දැනගැනීම කණගාටුවට කරුණකි, නමුත් ඔබ තනිවී නැති බව කරුණාකර මතක තබා ගන්න. කරුණාකර ඔබ විශ්වාස කරන වැඩිහිටියෙකු හෝ ඔබගේ ගුරුවරයා සමඟ වහාම කතා කරන්න. ඔබට උපකාර කිරීමට සෑම විටම සහය ඇත. | |
| `distress.helpline_heading` | Support & Crisis Helplines: | உதவி மற்றும் ஆதரவு அவசர எண்கள்: | සහාය සහ හදිසි උපකාරක දුරකථන අංක: | |
| `distress.helpline_ccc` | CCCline Mental Health Helpline (Toll-Free, 24/7): 1333 | CCCline மனநல அவசர உதவி எண் (இலவசம், 24/7): 1333 | CCCline මානසික සෞඛ්‍ය උපකාරක අංකය (නොමිලේ, 24/7): 1333 | |
| `distress.helpline_nimh` | National Institute of Mental Health Helpline: 1926 | தேசிய மனநல நிறுவனம் (NIMH) உதவி எண்: 1926 | ජාතික මානසික සෞඛ්‍ය ආයතනය (NIMH) උපකාරක අංකය: 1926 | |
| `distress.helpline_sumithrayo` | Sri Lanka Sumithrayo Crisis Line: 011-2696666 | இலங்கை சுமித்ரயோ அவசர உதவி எண்: 011-2696666 | ශ්‍රී ලංකා සුමිත්‍රයෝ උපකාරක සේවාව: 011-2696666 | |

---

## 2. Locked-Study State Notices
*Displayed on the public website and locked dashboards explaining why study notes and syllabus AI search are restricted.*

| Key / Context | English Meaning | Tamil (தமிழ்) | Sinhala (සිංහල) | Reviewer Notes |
|---|---|---|---|---|
| `locked.visitor` | Study note search is available for enrolled students. Please log in or register for classes to search your course materials. | கற்றல் குறிப்புகள் பதிவுசெய்த மாணவர்களுக்கு மட்டுமே கிடைக்கும். உங்கள் பாடக் குறிப்புகளைத் தேட தயவுசெய்து உள்நுழையவும் அல்லது வகுப்புகளில் பதிவுசெய்யவும். | අධ්‍යයන සටහන් සෙවීම ලියාපදිංචි සිසුන්ට පමණක් ලබාගත හැක. ඔබේ පාඨමාලා සටහන් සෙවීම සඳහා කරුණාකර ප්‍රවේශ වන්න හෝ පන්ති සඳහා ලියාපදිංචි වන්න. | |
| `locked.pending_approval` | Your student registration is pending approval by the administration. You will have full access to study materials once approved. | உங்கள் மாணவர் பதிவு நிர்வாகத்தின் அனுமதிக்காக காத்திருக்கிறது. அனுமதி வழங்கப்பட்டதும் நீங்கள் பாடக் குறிப்புகளை அணுகலாம். | ඔබගේ ශිෂ්‍ය ලියාපදිංචිය පරිපාලනයේ අනුමැතිය අපේක්ෂාවෙන් පවතී. අනුමත වූ පසු ඔබට අධ්‍යයන සටහන් සම්පූර්ණයෙන්ම ලබාගත හැක. | |
| `locked.payment_due` | Your monthly payment is currently due. Please settle your class fees to continue searching your study notes. | உங்கள் மாதாந்திரக் கட்டணம் நிலுவையில் உள்ளது. பாடக் குறிப்புகளைத் தொடர்ந்து தேட தயவுசெய்து உங்கள் வகுப்புக் கட்டணத்தைச் செலுத்தவும். | ඔබගේ මාසික පන්ති ගාස්තු ගෙවීම ප්‍රමාද වී ඇත. අධ්‍යයන සටහන් සෙවීම දිගටම කරගෙන යාමට කරුණාකර ඔබගේ පන්ති ගාස්තු ගෙවන්න. | |
| `locked.deactivated` | Your account is deactivated. Please contact the institute administration. | உங்கள் கணக்கு முடக்கப்பட்டுள்ளது. தயவுசெய்து கல்வி நிறுவன நிர்வாகத்தைத் தொடர்பு கொள்ளவும். | ඔබගේ ගිණුම අක්‍රිය කර ඇත. කරුණාකර ආයතනයේ පරිපාලනය අමතන්න. | |
| `locked.not_a_student` | The study assistant is designed for student learning. Teachers and administrators have access to administrative dashboards. | கற்றல் உதவியாளர் மாணவர்களுக்கானது. ஆசிரியர்கள் மற்றும் நிர்வாகிகளுக்கான வசதிகள் தனியான கட்டுப்பாட்டுப் பலகையில் உள்ளன. | අධ්‍යයන සහායකයා සිසුන්ගේ ප්‍රයෝජනය සඳහා නිර්මාණය කර ඇත. ගුරුවරුන් සහ පරිපාලකයින් සඳහා වෙනම පුවරු වෙන් කර ඇත. | |
| `locked.parent_no_child` | Please select a student profile using the profile switcher to view their study materials. | பாடக் குறிப்புகளைப் பார்வையிட சுயவிவர மாற்றியைப் பயன்படுத்தி மாணவர் சுயவிவரத்தைத் தேர்ந்தெடுக்கவும். | පාඩම් සටහන් බැලීමට කරුණාකර පැතිකඩ මාරුව හරහා ශිෂ්‍ය පැතිකඩක් තෝරන්න. | |
| `locked.missing_profile` | Your student profile is missing grade or medium information. Please update your profile to access study materials. | உங்கள் சுயவிவரத்தில் வகுப்பு அல்லது மொழி விவரங்கள் பூர்த்தி செய்யப்படவில்லை. பாடக் குறிப்புகளைப் பெற சுயவிவரத்தைப் புதுப்பிக்கவும். | ඔබගේ පැතිකඩෙහි ශ්‍රේණිය හා මාධ්‍යය ඇතුළත් කර නොමැත. පාඩම් සටහන් ලබා ගැනීමට කරුණාකර පැතිකඩ යාවත්කාලීන කරන්න. | |

---

## 3. Student Study Assistant UI & AI Chat
*Interactive chat components, status badges, counters, and assistive screen reader text in the student area.*

| Key / Context | English Meaning | Tamil (தமிழ்) | Sinhala (සිංහල) | Reviewer Notes |
|---|---|---|---|---|
| `study.first_time_notice` | AI answers may contain mistakes. The assistant answers only using your teacher's uploaded notes and past papers. | செயற்கை நுண்ணறிவு பதில்களில் தவறுகள் இருக்கலாம். இந்த உதவியாளர் உங்கள் ஆசிரியர் பதிவேற்றிய குறிப்புகள் மற்றும் கடந்தகால வினாத்தாள்களை மட்டுமே பயன்படுத்தி பதிலளிக்கிறது. | කෘත්‍රිම බුද්ධි පිළිතුරු වල දෝෂ තිබිය හැක. මෙම සහායකයා පිළිතුරු සපයන්නේ ඔබගේ ගුරුවරයා විසින් එක් කරන ලද සටහන් සහ පසුගිය විභාග ප්‍රශ්න පත්‍ර පමණක් භාවිත කරමිනි. | |
| `study.daily_usage_counter` | Daily questions used: {used} / {limit} | இன்றைய வினாக்கள் பயன்பாடு: {used} / {limit} | අද දින භාවිතා කළ ප්‍රශ්න: {used} / {limit} | |
| `study.new_chat_btn` | New Chat | புதிய உரையாடல் | නව සංවාදයක් | |
| `study.history_btn` | Recent Questions | முந்தைய வினாக்கள் | මෑත ප්‍රශ්න | |
| `study.history_title` | Recent Question History | முந்தைய வினாக்களின் வரலாறு | මෑත ප්‍රශ්න ඉතිහාසය | |
| `study.history_empty` | No previous questions recorded yet. | இதுவரை எந்த முந்தைய வினாக்களும் பதிவு செய்யப்படவில்லை. | මෙතෙක් කිසිදු ප්‍රශ්නයක් වාර්තා වී නොමැත. | |
| `study.copy_btn` | Copy Answer | பதிலை நகலெடு | පිළිතුර පිටපත් කරන්න | |
| `study.copied_btn` | Copied! | நகலெடுக்கப்பட்டது! | පිටපත් කළා! | |
| `study.retry_btn` | Retry Question | வினாவை மீண்டும் முயற்சி செய் | නැවත උත්සාහ කරන්න | |
| `study.finding_answers` | Finding answers from your teacher's notes... | உங்கள் ஆசிரியரின் குறிப்புகளில் இருந்து பதில்களைத் தேடுகிறது... | ඔබගේ ගුරු සටහන් වලින් පිළිතුරු සොයමින් පවතී... | |
| `study.input_placeholder` | Ask any question from your teacher's notes... | உங்கள் ஆசிரியர் குறிப்புகளில் இருந்து ஏதேனும் ஒரு வினாவைக் கேளுங்கள்... | ඔබගේ ගුරු සටහන් වලින් ඕනෑම ප්‍රශ්නයක් අසන්න... | |
| `study.no_notes_found` | I couldn't find information about that in your teacher's uploaded notes. Please ask your teacher to upload notes for this topic. | உங்கள் ஆசிரியர் பதிவேற்றிய குறிப்புகளில் இதைப் பற்றிய தகவல் கிடைக்கவில்லை. தயவுசெய்து இந்தத் தலைப்பிற்கான குறிப்புகளைப் பதிவேற்றுமாறு ஆசிரியரிடம் கேளுங்கள். | ඔබගේ ගුරුවරයා විසින් එක් කරන ලද සටහන් වල මේ පිළිබඳ තොරතුරු සොයාගත නොහැකි විය. කරුණාකර මෙම මාතෘකාව සඳහා සටහන් එක් කරන ලෙස ඔබගේ ගුරුවරයාගෙන් ඉල්ලන්න. | |
| `study.safety_blocked` | This question or response was blocked by our child safety filters. Please keep questions focused on school subjects. | இந்த வினா அல்லது பதில் எமது மாணவர் பாதுகாப்பு வடிகட்டியால் தடுக்கப்பட்டது. தயவுசெய்து பாடசாலைப் பாடங்கள் தொடர்பான வினாக்களை மட்டும் கேளுங்கள். | මෙම ප්‍රශ්නය හෝ පිළිතුර අපගේ ළමා ආරක්ෂණ පෙරහන මඟින් අවහිර කරන ලදී. කරුණාකර පාසල් විෂයයන්ට අදාළ ප්‍රශ්න පමණක් අසන්න. | |
| `study.ocr_uncertainty_badge` | Note: Some text in this cited note was scanned and may have small readability errors. | குறிப்பு: இந்த குறிப்பில் உள்ள சில பகுதிகள் படப்பிரதியாக (OCR) வாசிக்கப்பட்டதால் சிறிய எழுத்துப் பிழைகள் இருக்கலாம். | සටහන: මෙම සටහනේ සමහර කොටස් ස්කෑන් කර ඇති බැවින් සුළු කියවීමේ දෝෂ තිබිය හැක. | |

---

## 4. Teacher Materials & AI Knowledge Ingestion
*Statuses, hints, and error explanations on teacher upload and materials listing screens.*

| Key / Context | English Meaning | Tamil (தமிழ்) | Sinhala (සිංහල) | Reviewer Notes |
|---|---|---|---|---|
| `materials.grade_hint` | Grade controls which students can see this file and AI search. | இந்த ஆவணத்தை எந்த வகுப்பு மாணவர்கள் பார்க்கவும் AI தேடலில் பயன்படுத்தவும் முடியும் என்பதை 'வகுப்பு' தீர்மானிக்கிறது. | මෙම ගොනුව බැලීමට සහ AI සෙවීමට හැකි සිසුන් කවුරුන්ද යන්න ශ්‍රේණිය මඟින් පාලනය වේ. | |
| `materials.duplicate_hash_warning` | An identical file has already been uploaded in this institute ({title}). | இதே ஆவணம் ஏற்கனவே இந்த நிறுவனத்தில் பதிவேற்றப்பட்டுள்ளது ({title}). | මෙයට සමාන ගොනුවක් මෙම ආයතනය තුළ දැනටමත් උඩුගත කර ඇත ({title}). | |
| `materials.status_ready` | Ready | தயார் (தேடலில் உள்ளது) | සූදානම් (සෙවුමට සක්‍රියයි) | |
| `materials.status_processing` | Processing | செயலாக்கத்தில் உள்ளது | සකසමින් පවතී | |
| `materials.status_needs_clearer` | Needs Clearer File | தெளிவான ஆவணம் தேவை | වඩා පැහැදිලි ගොනුවක් අවශ්‍යයි | |
| `materials.status_failed` | Failed | தோல்வியடைந்தது | අසාර්ථක විය | |
| `materials.status_not_included` | Not Included | தேடலில் சேர்க்கப்படவில்லை | සෙවුමට ඇතුළත් කර නැත | |
| `materials.status_unsupported` | Unsupported Format | ஆதரிக்கப்படாத கோப்பு வடிவம் | සහාය නොදක්වන ගොනු ආකෘතියකි | |
| `materials.low_quality_explanation` | The text in this document could not be read clearly by our reader. Please upload a clearer PDF or typed digital document. | இந்த ஆவணத்தில் உள்ள உரையை எமது கணினியால் தெளிவாக வாசிக்க முடியவில்லை. தயவுசெய்து தெளிவான PDF அல்லது தட்டச்சு செய்யப்பட்ட ஆவணத்தைப் பதிவேற்றவும். | මෙම ලේඛනයේ ඇති අකුරු පැහැදිලිව කියවීමට නොහැකි විය. කරුණාකර වඩාත් පැහැදිලි PDF හෝ ටයිප් කරන ලද ලේඛනයක් උඩුගත කරන්න. | |
| `materials.retry_action` | Retry AI Ingestion | மீண்டும் செயலாக்க முயற்சி செய் | නැවත සැකසීමට උත්සාහ කරන්න | |
| `materials.unsupported_doc_ppt` | Legacy format (.doc / .ppt) is unsupported. Please upload modern PDF or DOCX / PPTX files. | பழைய கோப்பு வடிவங்கள் (.doc / .ppt) ஆதரிக்கப்படவில்லை. தயவுசெய்து புதிய PDF, DOCX அல்லது PPTX கோப்புகளைப் பதிவேற்றவும். | පැරණි ගොනු ආකෘති (.doc / .ppt) සඳහා සහාය නොදක්වයි. කරුණාකර නවීන PDF හෝ DOCX / PPTX ගොනු උඩුගත කරන්න. | |

---

## 5. Website Public Assistant Widget
*Chat interface available to visitors on the landing page, classes, and contact pages.*

| Key / Context | English Meaning | Tamil (தமிழ்) | Sinhala (සිංහල) | Reviewer Notes |
|---|---|---|---|---|
| `public_widget.title` | Institute Assistant | கல்வி நிறுவன உதவியாளர் | ආයතනික සහායක | |
| `public_widget.welcome` | Hello! How can I help you today? Ask me about classes, timetables, fees, or how to register. | வணக்கம்! இன்று உங்களுக்கு எவ்வாறு உதவ முடியும்? வகுப்புகள், நேர அட்டவணை, கட்டணங்கள் அல்லது பதிவு செய்வது பற்றி என்னிடம் கேளுங்கள். | ආයුබෝවන්! අද මට ඔබට උදව් කළ හැක්කේ කෙසේද? පන්ති, කාලසටහන්, ගාස්තු හෝ ලියාපදිංචි වන ආකාරය ගැන මගෙන් විමසන්න. | |
| `public_widget.visitor_disclaimer` | Official information only. For student note search, please sign in. | உத்தியோகபூர்வ பொதுத் தகவல்கள் மட்டுமே. பாடக் குறிப்புகளைத் தேட தயவுசெய்து உள்நுழையவும். | නිල තොරතුරු පමණි. ශිෂ්‍ය පාඩම් සටහන් සෙවීම සඳහා කරුණාකර ප්‍රවේශ වන්න. | |
| `public_widget.suggest_timetable` | Class Timetable & Days | வகுப்பு நேர அட்டவணை மற்றும் நாட்கள் | පන්ති කාලසටහන සහ දින | |
| `public_widget.suggest_subjects` | Available Subjects & Grades | கிடைக்கும் பாடங்கள் மற்றும் வகுப்புகள் | පවතින විෂයයන් සහ ශ්‍රේණි | |
| `public_widget.suggest_fees` | Tuition Fee Details | வகுப்புக் கட்டண விவரங்கள் | පන්ති ගාස්තු විස්තර | |
| `public_widget.suggest_contact` | Contact & Operating Hours | தொடர்பு மற்றும் வேலை நேரம் | සම්බන්ධ කර ගැනීම සහ වේලාවන් | |
| `public_widget.ask_placeholder` | Ask a question about our institute... | எமது கல்வி நிறுவனம் பற்றி ஒரு வினாவைக் கேளுங்கள்... | අපගේ ආයතනය පිළිබඳ ප්‍රශ්නයක් අසන්න... | |

---

## 6. General Navigation, Mediums, and Metadata

| Key / Context | English Meaning | Tamil (தமிழ்) | Sinhala (සිංහල) | Reviewer Notes |
|---|---|---|---|---|
| `medium.tamil` | Tamil Medium | தமிழ் மொழி மூலம் | දෙමළ මාධ්‍යය | |
| `medium.english` | English Medium | ஆங்கில மொழி மூலம் | ඉංග්‍රීසි මාධ්‍යය | |
| `medium.sinhala` | Sinhala Medium | சிங்கள மொழி மூலம் | සිංහල මාධ්‍යය | |
| `medium.all` | All Mediums | அனைத்து மொழிகளும் | සියලුම මාධ්‍යයන් | |
| `common.save` | Save Changes | மாற்றங்களைச் சேமிக்கவும் | වෙනස්කම් සුරකින්න | |
| `common.cancel` | Cancel | ரத்து செய் | අවලංගු කරන්න | |
| `common.loading` | Loading... | ஏற்றுகிறது... | පූරණය වෙමින් පවතී... | |
| `common.error` | An unexpected error occurred. | எதிர்பாராத பிழை ஏற்பட்டது. | අනපේක්ෂිත දෝෂයක් සිදු විය. | |
