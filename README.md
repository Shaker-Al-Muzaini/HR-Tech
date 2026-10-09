<div align="center">

# 🎯 Smart HR Tech System
### نظام المقابلات الذكي

**منصة هجينة متكاملة لأتمتة المقابلات الوظيفية مع تحليل AI حي وتقارير تلقائية**

[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Vue](https://img.shields.io/badge/Vue-3-4FC08D?style=for-the-badge&logo=vue.js&logoColor=white)](https://vuejs.org)
[![FastAPI](https://img.shields.io/badge/FastAPI-0.115-009688?style=for-the-badge&logo=fastapi&logoColor=white)](https://fastapi.tiangolo.com)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-336791?style=for-the-badge&logo=postgresql&logoColor=white)](https://www.postgresql.org)
[![Python](https://img.shields.io/badge/Python-3.11-3776AB?style=for-the-badge&logo=python&logoColor=white)](https://www.python.org)
[![WebRTC](https://img.shields.io/badge/WebRTC-P2P-333333?style=for-the-badge&logo=webrtc&logoColor=white)](https://webrtc.org)

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg?style=flat-square)](https://opensource.org/licenses/MIT)
[![Status](https://img.shields.io/badge/Status-Production-success?style=flat-square)]()
[![PRs Welcome](https://img.shields.io/badge/PRs-Welcome-brightgreen.svg?style=flat-square)](CONTRIBUTING.md)

</div>

---

## 📋 جدول المحتويات

- [نظرة عامة](#-نظرة-عامة)
- [الميزات الرئيسية](#-الميزات-الرئيسية)
- [لقطات الشاشة](#-لقطات-الشاشة)
- [الهيكل التقني](#-الهيكل-التقني)
- [البنية المعمارية](#-البنية-المعمارية)
- [التثبيت السريع](#-التثبيت-السريع)
- [دليل الاستخدام](#-دليل-الاستخدام)
- [تكامل n8n](#-تكامل-n8n)
- [بنية المشروع](#-بنية-المشروع)
- [API Endpoints](#-api-endpoints)
- [قاعدة البيانات](#-قاعدة-البيانات)
- [خارطة الطريق](#-خارطة-الطريق)
- [المساهمة](#-المساهمة)
- [الترخيص](#-الترخيص)

---

## 🌟 نظرة عامة

**Smart HR Tech System** هو نظام متكامل لأتمتة المقابلات الوظيفية، يجمع بين:
- 🎥 **مكالمة فيديو ثنائية الاتجاه** (WebRTC P2P)
- 🧠 **تحليل ذكي حي** (Prosody + FACS + YOLO)
- 📊 **تقارير تلقائية** بدرجات موضوعية
- 🤖 **تكامل n8n** لأتمتة المهام الخارجية
- 🌍 **دعم كامل للعربية** (RTL)

النظام مصمم ليكون **بديلاً ذكياً** للمقابلات التقليدية، بتقديم مؤشرات موضوعية عن المرشحين تساعد فريق HR على اتخاذ قرارات مدروسة.

### 🎯 لمن هذا النظام؟

| الفئة | الفائدة |
|---|---|
| **فرق HR** | توفير وقت المراجعة + مؤشرات موضوعية + أرشفة كاملة |
| **الشركات الناشئة** | نظام مقابلات احترافي بدون تكاليف SaaS شهرية |
| **المطورون** | قاعدة كود نظيفة وقابلة للتوسع + تكاملات جاهزة |

---

## ✨ الميزات الرئيسية

<table>
<tr>
<td width="50%" valign="top">

### 🎥 مقابلات فيديو حية
- مكالمة WebRTC ثنائية الاتجاه
- غرفة انتظار + موافقة HR
- Grace Period للمشرف
- دعم اتصال غير محدود

### 🧠 تحليل AI حي
- **Prosody**: نبرة الصوت، الإيقاع، الصمت
- **FACS**: حركات الوجه التفصيلية
- **YOLO**: كشف الهاتف/الأشخاص
- **Quality Gate**: تقييم جودة الإشارة

</td>
<td width="50%" valign="top">

### 📊 تقارير ذكية
- 4 درجات: النزاهة، الثقة، الاتزان، الجودة
- Score Ring متحرك
- ملاحظات تلقائية
- تفاصيل تقنية قابلة للتوسع

### 🤖 تكامل n8n
- 6 أحداث تلقائية
- Queue-based (بدون تأخير)
- قابل للتعطيل/التفعيل
- Retry تلقائي

</td>
</tr>
<tr>
<td width="50%" valign="top">

### 🎨 واجهة احترافية
- Sidebar جانبي موحّد
- Toast notifications موحّدة
- Confirm Modals مخصصة
- أصوات UI (بدون ملفات)

### 📚 سجل المقابلات
- Grid + List views
- فلاتر متقدمة (نوع + قرار + تاريخ)
- 6 بطاقات إحصائية تفاعلية
- Pagination احترافي

</td>
<td width="50%" valign="top">

### 🔐 أمان وموثوقية
- Route Guards للحالات المنتهية
- Backfill تلقائي للبيانات
- Observer Pattern للمزامنة
- Soft Deletes للسجلات

### 🌐 تكاملات جاهزة
- REST API كامل
- Webhooks لـ n8n
- قابل للتوسع بسهولة
- دعم PostgreSQL JSONB

</td>
</tr>
</table>

---

## 📸 لقطات الشاشة

### 🏠 لوحة التحكم الرئيسية
> إحصائيات فورية + فلاتر ذكية + جدول تفاعلي

![Dashboard](docs/screenshots/01-dashboard.png)

---

### ➕ إنشاء مقابلة جديدة
> Modal احترافي مع DateTimePicker مخصص

![Create Interview](docs/screenshots/02-create-interview.png)

---

### 🎥 غرفة المقابلة (HR)
> مؤشرات AI مباشرة + اقتراحات أسئلة + تنبيهات

![Live Interview](docs/screenshots/03-live-interview.png)

---

### 👤 غرفة المرشح
> واجهة عربية أنيقة + نصائح + معايرة تلقائية

![Candidate Room](docs/screenshots/04-candidate-room.png)

---

### 📊 التقرير النهائي
> درجات موضوعية + ملاحظات + قرار HR

![Report](docs/screenshots/05-report.png)

---

### 📚 سجل المقابلات المكتملة
> 6 بطاقات إحصائية + Toggle بين Grid/List + فلاتر متقدمة

![Completed List](docs/screenshots/06-completed-list.png)

---

### 🎯 تفاصيل مقابلة مكتملة
> Hero card + Score Ring + KPI + قرار HR

![Completed Detail](docs/screenshots/07-completed-detail.png)

---

## 🛠 الهيكل التقني

### 💻 Stack التطوير

<table>
<tr>
<td align="center" width="20%">
<img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/laravel/laravel-original.svg" width="60" /><br>
<b>Laravel 11</b><br>
<sub>Backend Framework</sub>
</td>
<td align="center" width="20%">
<img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/vuejs/vuejs-original.svg" width="60" /><br>
<b>Vue 3</b><br>
<sub>Frontend SPA</sub>
</td>
<td align="center" width="20%">
<img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/fastapi/fastapi-original.svg" width="60" /><br>
<b>FastAPI</b><br>
<sub>AI Engine</sub>
</td>
<td align="center" width="20%">
<img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/postgresql/postgresql-original.svg" width="60" /><br>
<b>PostgreSQL 16</b><br>
<sub>Database</sub>
</td>
<td align="center" width="20%">
<img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/redis/redis-original.svg" width="60" /><br>
<b>Redis</b><br>
<sub>Cache + Queue</sub>
</td>
</tr>
</table>
