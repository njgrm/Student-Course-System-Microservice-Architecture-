/**
 * Student Course System — Gateway Dashboard
 *
 * Single-page dashboard connecting to three independent Laravel microservices
 * via Axios. Each section loads independently with graceful degradation.
 */

import axios from 'axios';

// ========== Axios Config: no-cache + timeout ==========
axios.defaults.timeout = 5000;
axios.defaults.adapter = 'fetch';
axios.interceptors.request.use((config) => {
    config.fetchOptions = { cache: 'no-store' };
    if (config.method === 'get') {
        config.params = { ...config.params, _t: Date.now() };
    }
    return config;
});

// ========== Service Base URLs ==========
const STUDENT_API    = 'http://localhost:8001/api';
const COURSE_API     = 'http://localhost:8002/api';
const ENROLLMENT_API = 'http://localhost:8003/api';

// ========== Service Status Tracking ==========
const serviceStatus = {
    students: false,
    courses: false,
    enrollments: false,
};

// ========== DOM References ==========

// Students
const studentForm      = document.getElementById('student-form');
const studentFormBox   = document.getElementById('student-form-container');
const studentNameIn    = document.getElementById('student-name');
const studentEmailIn   = document.getElementById('student-email');
const studentAgeIn     = document.getElementById('student-age');
let   studentsTbody    = document.getElementById('students-tbody');
const studentCount     = document.getElementById('student-count');
const studentStatus    = document.getElementById('student-status');
const studentTableArea = document.getElementById('student-table-area');

// Courses
const courseForm      = document.getElementById('course-form');
const courseFormBox   = document.getElementById('course-form-container');
const courseNameIn    = document.getElementById('course-name');
const courseDescIn    = document.getElementById('course-desc');
const courseCreditsIn = document.getElementById('course-credits');
let   coursesTbody    = document.getElementById('courses-tbody');
const courseCount     = document.getElementById('course-count');
const courseStatus    = document.getElementById('course-status');
const courseTableArea = document.getElementById('course-table-area');

// Enrollments
const enrollForm       = document.getElementById('enrollment-form');
const enrollFormBox    = document.getElementById('enrollment-form-container');
const enrollStudentSel = document.getElementById('enroll-student');
const enrollCourseSel  = document.getElementById('enroll-course');
const enrollSubmitBtn  = document.getElementById('enroll-submit-btn');
const enrollDepWarn    = document.getElementById('enrollment-dep-warning');
let   enrollmentsTbody = document.getElementById('enrollments-tbody');
const enrollmentCount  = document.getElementById('enrollment-count');
const enrollmentStatus = document.getElementById('enrollment-status');
const enrollTableArea  = document.getElementById('enrollment-table-area');

// Toast
const toastContainer = document.getElementById('toast-container');

// ========== Utility: HTML Escaping ==========

function esc(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// ========== Toast Notifications ==========

function showToast(message, type = 'success', title = null) {
    const isSuccess = type === 'success';
    const autoClose = 3500;
    const color = isSuccess ? '#10b981' : '#ef4444';

    const toast = document.createElement('div');
    toast.className = 'flex items-start gap-3 bg-zinc-900 border border-zinc-700/50 shadow-xl rounded-lg px-4 py-3 min-w-[320px] max-w-sm relative overflow-hidden toast-enter';
    toast.style.borderLeftWidth = '3px';
    toast.style.borderLeftColor = color;

    const iconSvg = isSuccess
        ? '<svg class="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>'
        : '<svg class="w-5 h-5 text-red-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>';

    const titleText = title || (isSuccess ? 'Success' : 'Error');

    toast.innerHTML = `
        ${iconSvg}
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-zinc-100">${esc(titleText)}</p>
            <p class="text-sm text-zinc-400 mt-0.5">${esc(message)}</p>
        </div>
        <button class="toast-dismiss text-zinc-500 hover:text-zinc-300 shrink-0 mt-0.5" aria-label="Dismiss">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
        </button>
        <div class="toast-progress absolute bottom-0 left-0 h-0.5" style="background:${color};width:100%;"></div>
    `;

    toast.querySelector('.toast-dismiss').addEventListener('click', () => dismissToast(toast));

    const progressBar = toast.querySelector('.toast-progress');
    progressBar.style.transition = `width ${autoClose}ms linear`;
    requestAnimationFrame(() => { progressBar.style.width = '0%'; });

    toastContainer.appendChild(toast);
    const timer = setTimeout(() => dismissToast(toast), autoClose);
    toast._timer = timer;
}

function dismissToast(toast) {
    if (toast._dismissed) return;
    toast._dismissed = true;
    clearTimeout(toast._timer);
    toast.style.transition = 'opacity 0.3s, transform 0.3s';
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(100%)';
    setTimeout(() => toast.remove(), 300);
}

// ========== Status Badge Helpers ==========

function setOnline(badge) {
    badge.innerHTML = '<span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span></span> Online';
    badge.className = 'inline-flex items-center gap-1.5 bg-emerald-500/10 text-emerald-400 text-[10px] font-medium px-2 py-0.5 rounded-full';
}

function setOffline(badge) {
    badge.innerHTML = '<span class="inline-flex rounded-full h-2 w-2 bg-red-400"></span> Offline';
    badge.className = 'inline-flex items-center gap-1.5 bg-red-500/10 text-red-400 text-[10px] font-medium px-2 py-0.5 rounded-full';
}

function showUnavailable(tableArea, formBox, retryFn) {
    formBox.classList.add('hidden');
    tableArea.innerHTML = `
        <div class="bg-red-500/5 border border-red-500/20 text-red-300 p-6 rounded-lg text-center">
            <svg class="w-8 h-8 mx-auto mb-2 text-red-400/60" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
            <p class="font-medium text-sm text-zinc-300 mb-3">Service unavailable</p>
            <button class="retry-btn inline-flex items-center gap-1.5 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 border border-zinc-700/50 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182"/></svg>
                Retry
            </button>
        </div>`;
    if (retryFn) {
        tableArea.querySelector('.retry-btn').addEventListener('click', retryFn);
    }
}

function showFormBox(formBox) {
    formBox.classList.remove('hidden');
}

// ========== Skeleton Loading ==========

function showSkeleton(tbody, cols = 4) {
    const rows = Array.from({ length: 3 }, () => {
        const tds = Array.from({ length: cols }, (_, i) => {
            const w = i === 0 ? 'w-28' : i === cols - 1 ? 'w-14 ml-auto' : 'w-20';
            return `<td class="px-3 py-3"><div class="h-3 ${w} bg-zinc-800 rounded animate-pulse"></div></td>`;
        }).join('');
        return `<tr class="border-b border-zinc-800/30">${tds}</tr>`;
    }).join('');
    tbody.innerHTML = rows;
}

// ========== Ensure Table Exists (rebuild after showUnavailable) ==========

function ensureTable(tableArea, tableId, tbodyId, headers) {
    let tbody = tableArea.querySelector(`#${tbodyId}`);
    if (!tbody) {
        const headerRow = headers.map(([label, align]) =>
            `<th class="px-3 py-2 text-${align} text-[11px] font-semibold text-zinc-500 uppercase tracking-wider">${label}</th>`
        ).join('');
        tableArea.innerHTML = `<table id="${tableId}" class="w-full text-sm">
            <thead><tr class="border-b border-zinc-800/50">${headerRow}</tr></thead>
            <tbody id="${tbodyId}"></tbody>
        </table>`;
        tbody = tableArea.querySelector(`#${tbodyId}`);
    }
    return tbody;
}

// ========== Confirm Modal ==========

let modalResolve = null;
const confirmModal = document.getElementById('confirm-modal');
const confirmModalMsg = document.getElementById('confirm-modal-message');

function showConfirmModal(message) {
    return new Promise((resolve) => {
        modalResolve = resolve;
        confirmModalMsg.textContent = message;
        confirmModal.classList.remove('hidden');
        requestAnimationFrame(() => confirmModal.querySelector('.modal-panel').classList.add('modal-visible'));
    });
}

function hideConfirmModal(result) {
    confirmModal.querySelector('.modal-panel').classList.remove('modal-visible');
    setTimeout(() => confirmModal.classList.add('hidden'), 150);
    if (modalResolve) {
        modalResolve(result);
        modalResolve = null;
    }
}

document.getElementById('confirm-modal-cancel').addEventListener('click', () => hideConfirmModal(false));
document.getElementById('confirm-modal-confirm').addEventListener('click', () => hideConfirmModal(true));
confirmModal.addEventListener('click', (e) => { if (e.target === confirmModal) hideConfirmModal(false); });

// ========== STUDENTS ==========

async function loadStudents() {
    studentsTbody = ensureTable(studentTableArea, 'students-table', 'students-tbody', [
        ['Name', 'left'], ['Email', 'left'], ['Age', 'left'], ['Actions', 'right']
    ]);
    showFormBox(studentFormBox);
    showSkeleton(studentsTbody, 4);
    try {
        const { data } = await axios.get(`${STUDENT_API}/students`);
        const students = data.data ?? data;
        const count = data.count ?? students.length;

        serviceStatus.students = true;
        setOnline(studentStatus);
        showFormBox(studentFormBox);
        studentCount.textContent = count;

        if (students.length === 0) {
            studentsTbody.innerHTML = '<tr><td colspan="4" class="px-3 py-8 text-center text-zinc-600 italic text-sm">No students yet.</td></tr>';
            return;
        }

        studentsTbody.innerHTML = students.map(s => `
            <tr class="hover:bg-zinc-800/30 transition-colors border-b border-zinc-800/30 last:border-0">
                <td class="px-3 py-2.5 font-medium text-zinc-200 text-sm">${esc(s.full_name)}</td>
                <td class="px-3 py-2.5 text-zinc-400 text-sm">${esc(s.email)}</td>
                <td class="px-3 py-2.5 text-zinc-400 text-sm font-mono">${s.age}</td>
                <td class="px-3 py-2.5 text-right">
                    <button onclick="window.deleteStudent(${s.id})"
                        class="inline-flex items-center gap-1 text-red-400 hover:text-red-300 hover:bg-red-500/10 px-2 py-1 rounded text-sm font-medium transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                        Delete
                    </button>
                </td>
            </tr>
        `).join('');
    } catch {
        serviceStatus.students = false;
        setOffline(studentStatus);
        studentCount.textContent = '–';
        showUnavailable(studentTableArea, studentFormBox, loadStudents);
    }
}

studentForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const full_name = studentNameIn.value.trim();
    const email     = studentEmailIn.value.trim();
    const age       = parseInt(studentAgeIn.value, 10);

    if (!full_name || !email || !age || age <= 0) {
        showToast('Please fill in all student fields correctly.', 'error');
        return;
    }

    try {
        await axios.post(`${STUDENT_API}/students`, { full_name, email, age });
        showToast('Student added successfully!');
        studentForm.reset();
        await loadStudents();
        loadEnrollmentDropdowns();
    } catch (err) {
        const msg = err.response?.data?.message || err.response?.data?.error || err.message;
        showToast(msg, 'error');
    }
});

window.deleteStudent = async function(id) {
    const confirmed = await showConfirmModal('Delete this student? Their enrollments will also be removed.');
    if (!confirmed) return;

    try {
        await axios.delete(`${STUDENT_API}/students/${id}`);
        showToast('Student deleted.');
        await Promise.allSettled([loadStudents(), loadEnrollments()]);
        loadEnrollmentDropdowns();
    } catch (err) {
        const msg = err.response?.data?.message || err.message;
        showToast(msg, 'error');
    }
};

// ========== COURSES ==========

async function loadCourses() {
    coursesTbody = ensureTable(courseTableArea, 'courses-table', 'courses-tbody', [
        ['Name', 'left'], ['Description', 'left'], ['Credits', 'left'], ['Actions', 'right']
    ]);
    showFormBox(courseFormBox);
    showSkeleton(coursesTbody, 4);
    try {
        const { data } = await axios.get(`${COURSE_API}/courses`);
        const courses = data.data ?? data;
        const count = data.count ?? courses.length;

        serviceStatus.courses = true;
        setOnline(courseStatus);
        showFormBox(courseFormBox);
        courseCount.textContent = count;

        if (courses.length === 0) {
            coursesTbody.innerHTML = '<tr><td colspan="4" class="px-3 py-8 text-center text-zinc-600 italic text-sm">No courses yet.</td></tr>';
            return;
        }

        coursesTbody.innerHTML = courses.map(c => `
            <tr class="hover:bg-zinc-800/30 transition-colors border-b border-zinc-800/30 last:border-0">
                <td class="px-3 py-2.5 font-medium text-zinc-200 text-sm">${esc(c.name)}</td>
                <td class="px-3 py-2.5 text-zinc-400 text-sm max-w-xs truncate">${esc(c.description)}</td>
                <td class="px-3 py-2.5 text-zinc-400">
                    <span class="inline-flex items-center bg-teal-500/10 text-teal-400 text-xs font-semibold px-2 py-0.5 rounded">${c.credits} cr</span>
                </td>
                <td class="px-3 py-2.5 text-right">
                    <button onclick="window.deleteCourse(${c.id})"
                        class="inline-flex items-center gap-1 text-red-400 hover:text-red-300 hover:bg-red-500/10 px-2 py-1 rounded text-sm font-medium transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                        Delete
                    </button>
                </td>
            </tr>
        `).join('');
    } catch {
        serviceStatus.courses = false;
        setOffline(courseStatus);
        courseCount.textContent = '–';
        showUnavailable(courseTableArea, courseFormBox, loadCourses);
    }
}

courseForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const name        = courseNameIn.value.trim();
    const description = courseDescIn.value.trim();
    const credits     = parseInt(courseCreditsIn.value, 10);

    if (!name || !description || !credits || credits <= 0) {
        showToast('Please fill in all course fields correctly.', 'error');
        return;
    }

    try {
        await axios.post(`${COURSE_API}/courses`, { name, description, credits });
        showToast('Course added successfully!');
        courseForm.reset();
        await loadCourses();
        loadEnrollmentDropdowns();
    } catch (err) {
        const msg = err.response?.data?.message || err.response?.data?.error || err.message;
        showToast(msg, 'error');
    }
});

window.deleteCourse = async function(id) {
    const confirmed = await showConfirmModal('Delete this course? Related enrollments will also be removed.');
    if (!confirmed) return;

    try {
        await axios.delete(`${COURSE_API}/courses/${id}`);
        showToast('Course deleted.');
        await Promise.allSettled([loadCourses(), loadEnrollments()]);
        loadEnrollmentDropdowns();
    } catch (err) {
        const msg = err.response?.data?.message || err.message;
        showToast(msg, 'error');
    }
};

// ========== ENROLLMENTS ==========

async function loadEnrollments() {
    enrollmentsTbody = ensureTable(enrollTableArea, 'enrollments-table', 'enrollments-tbody', [
        ['Student', 'left'], ['Course', 'left'], ['Enrolled At', 'left'], ['Actions', 'right']
    ]);
    showFormBox(enrollFormBox);
    showSkeleton(enrollmentsTbody, 4);
    try {
        const { data } = await axios.get(`${ENROLLMENT_API}/enrollments`);
        const enrollments = data.data ?? data;
        const count = data.count ?? enrollments.length;

        serviceStatus.enrollments = true;
        setOnline(enrollmentStatus);
        showFormBox(enrollFormBox);
        enrollmentCount.textContent = count;

        if (enrollments.length === 0) {
            enrollmentsTbody.innerHTML = '<tr><td colspan="4" class="px-3 py-8 text-center text-zinc-600 italic text-sm">No enrollments yet.</td></tr>';
            return;
        }

        enrollmentsTbody.innerHTML = enrollments.map(e => `
            <tr class="hover:bg-zinc-800/30 transition-colors border-b border-zinc-800/30 last:border-0">
                <td class="px-3 py-2.5">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-violet-500/15 text-violet-400 text-[10px] font-bold">
                            ${esc(e.student_name?.charAt(0)?.toUpperCase() || '?')}
                        </span>
                        <span class="text-sm font-medium text-zinc-200">${esc(e.student_name || 'Unknown')}</span>
                    </div>
                </td>
                <td class="px-3 py-2.5">
                    <span class="inline-flex items-center bg-teal-500/10 text-teal-400 text-xs font-semibold px-2 py-0.5 rounded">
                        ${esc(e.course_name || 'Unknown')}
                    </span>
                </td>
                <td class="px-3 py-2.5 text-zinc-500 text-sm font-mono">${new Date(e.enrolled_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</td>
                <td class="px-3 py-2.5 text-right">
                    <button onclick="window.deleteEnrollment(${e.id})"
                        class="inline-flex items-center gap-1 text-red-400 hover:text-red-300 hover:bg-red-500/10 px-2 py-1 rounded text-sm font-medium transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                        Remove
                    </button>
                </td>
            </tr>
        `).join('');
    } catch {
        serviceStatus.enrollments = false;
        setOffline(enrollmentStatus);
        enrollmentCount.textContent = '–';
        showUnavailable(enrollTableArea, enrollFormBox, loadEnrollments);
    }
}

// ========== Enrollment Dropdowns ==========

async function loadEnrollmentDropdowns() {
    const unavailable = [];

    // Load students dropdown
    try {
        const { data } = await axios.get(`${STUDENT_API}/students`);
        const students = data.data ?? data;
        enrollStudentSel.innerHTML = '<option value="">-- Choose a student --</option>';
        students.forEach(s => {
            enrollStudentSel.innerHTML += `<option value="${s.id}">${esc(s.full_name)} (${esc(s.email)})</option>`;
        });
        enrollStudentSel.disabled = false;
    } catch {
        enrollStudentSel.innerHTML = '<option value="">⚠️ Student service unavailable</option>';
        enrollStudentSel.disabled = true;
        unavailable.push('Student Service');
    }

    // Load courses dropdown
    try {
        const { data } = await axios.get(`${COURSE_API}/courses`);
        const courses = data.data ?? data;
        enrollCourseSel.innerHTML = '<option value="">-- Choose a course --</option>';
        courses.forEach(c => {
            enrollCourseSel.innerHTML += `<option value="${c.id}">${esc(c.name)} (${c.credits} credits)</option>`;
        });
        enrollCourseSel.disabled = false;
    } catch {
        enrollCourseSel.innerHTML = '<option value="">⚠️ Course service unavailable</option>';
        enrollCourseSel.disabled = true;
        unavailable.push('Course Service');
    }

    // Handle form enable/disable
    if (unavailable.length > 0) {
        enrollSubmitBtn.disabled = true;
        enrollSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        enrollDepWarn.textContent = `⚠️ Cannot enroll — ${unavailable.join(' and ')} ${unavailable.length === 1 ? 'is' : 'are'} unavailable.`;
        enrollDepWarn.classList.remove('hidden');
    } else {
        enrollSubmitBtn.disabled = false;
        enrollSubmitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        enrollDepWarn.classList.add('hidden');
    }
}

enrollForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const student_id = enrollStudentSel.value;
    const course_id  = enrollCourseSel.value;

    if (!student_id || !course_id) {
        showToast('Please select both a student and a course.', 'error');
        return;
    }

    try {
        await axios.post(`${ENROLLMENT_API}/enrollments`, { student_id, course_id });
        showToast('Student enrolled successfully!');
        enrollForm.reset();
        loadEnrollments();
    } catch (err) {
        const msg = err.response?.data?.message || err.response?.data?.error || err.message;
        showToast(msg, 'error');
    }
});

window.deleteEnrollment = async function(id) {
    const confirmed = await showConfirmModal('Remove this enrollment?');
    if (!confirmed) return;

    try {
        await axios.delete(`${ENROLLMENT_API}/enrollments/${id}`);
        showToast('Enrollment removed.');
        loadEnrollments();
    } catch (err) {
        const msg = err.response?.data?.message || err.message;
        showToast(msg, 'error');
    }
};

// ========== Initial Load + Service Polling ==========

const POLL_INTERVAL = 6000; // check offline services every 6 seconds

document.addEventListener('DOMContentLoaded', () => {
    // Load all three sections in parallel — each fails independently
    Promise.allSettled([
        loadStudents(),
        loadCourses(),
        loadEnrollments(),
    ]).then(() => {
        loadEnrollmentDropdowns();
        // Start background polling for offline services
        setInterval(pollServices, POLL_INTERVAL);
    });
});

/**
 * Lightweight ping: tries GET with short timeout, returns true if alive.
 */
async function isServiceUp(url) {
    try {
        await axios.get(url, { timeout: 3000 });
        return true;
    } catch {
        return false;
    }
}

/**
 * Background health-check that runs every POLL_INTERVAL ms.
 * - If a service was offline and comes back → full reload (shows data)
 * - If a service was online and goes down  → full reload (shows unavailable)
 * - If nothing changed → no-op (no flicker)
 * When student or course availability changes, enrollment dropdowns auto-refresh.
 */
async function pollServices() {
    const prevStudents    = serviceStatus.students;
    const prevCourses     = serviceStatus.courses;
    const prevEnrollments = serviceStatus.enrollments;

    // Ping all three in parallel
    const [studentsUp, coursesUp, enrollmentsUp] = await Promise.all([
        isServiceUp(STUDENT_API + '/students'),
        isServiceUp(COURSE_API + '/courses'),
        isServiceUp(ENROLLMENT_API + '/enrollments'),
    ]);

    // Only reload sections where status actually changed
    const reloads = [];
    if (studentsUp !== prevStudents)       reloads.push(loadStudents());
    if (coursesUp !== prevCourses)         reloads.push(loadCourses());
    if (enrollmentsUp !== prevEnrollments) reloads.push(loadEnrollments());

    if (reloads.length === 0) return; // no changes — skip

    await Promise.allSettled(reloads);

    // If student or course availability changed, refresh enrollment dropdowns
    if (studentsUp !== prevStudents || coursesUp !== prevCourses) {
        loadEnrollmentDropdowns();
    }
}
