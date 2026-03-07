/**
 * Student Course System — Gateway Dashboard
 *
 * Single-page dashboard connecting to three independent Laravel microservices
 * via Axios. Each section loads independently with graceful degradation.
 */

import axios from 'axios';

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
const studentsTbody    = document.getElementById('students-tbody');
const studentCount     = document.getElementById('student-count');
const studentStatus    = document.getElementById('student-status');
const studentTableArea = document.getElementById('student-table-area');

// Courses
const courseForm      = document.getElementById('course-form');
const courseFormBox   = document.getElementById('course-form-container');
const courseNameIn    = document.getElementById('course-name');
const courseDescIn    = document.getElementById('course-desc');
const courseCreditsIn = document.getElementById('course-credits');
const coursesTbody    = document.getElementById('courses-tbody');
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
const enrollmentsTbody = document.getElementById('enrollments-tbody');
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

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = type === 'success'
        ? 'bg-green-500 text-white px-4 py-2 rounded shadow text-sm font-medium'
        : 'bg-red-500 text-white px-4 py-2 rounded shadow text-sm font-medium';
    toast.textContent = message;
    toastContainer.appendChild(toast);
    setTimeout(() => {
        toast.style.transition = 'opacity 0.3s';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ========== Status Badge Helpers ==========

function setOnline(badge) {
    badge.textContent = '🟢 Online';
    badge.className = 'bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full';
}

function setOffline(badge) {
    badge.textContent = '🔴 Unavailable';
    badge.className = 'bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full';
}

function showUnavailable(tableArea, formBox) {
    formBox.classList.add('hidden');
    tableArea.innerHTML = `
        <div class="bg-amber-50 border border-amber-200 text-amber-800 p-4 rounded-lg text-center">
            <p class="font-medium">⚠️ Service unavailable — try again later</p>
        </div>`;
}

function showFormBox(formBox) {
    formBox.classList.remove('hidden');
}

// ========== STUDENTS ==========

async function loadStudents() {
    try {
        const { data } = await axios.get(`${STUDENT_API}/students`);
        const students = data.data ?? data;
        const count = data.count ?? students.length;

        serviceStatus.students = true;
        setOnline(studentStatus);
        showFormBox(studentFormBox);
        studentCount.textContent = count;

        if (students.length === 0) {
            studentsTbody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-gray-400 italic">No students yet.</td></tr>';
            return;
        }

        studentsTbody.innerHTML = students.map(s => `
            <tr class="hover:bg-slate-50 transition-colors">
                <td class="px-4 py-3 font-medium text-gray-900">${esc(s.full_name)}</td>
                <td class="px-4 py-3 text-gray-600">${esc(s.email)}</td>
                <td class="px-4 py-3 text-gray-600">${s.age}</td>
                <td class="px-4 py-3 text-right">
                    <button onclick="window.deleteStudent(${s.id})"
                        class="inline-flex items-center gap-1 text-red-600 hover:text-red-800 hover:bg-red-50 px-2 py-1 rounded text-sm font-medium transition-colors">
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
        showUnavailable(studentTableArea, studentFormBox);
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
    if (!confirm('Delete this student? Their enrollments will also be removed.')) return;

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
    try {
        const { data } = await axios.get(`${COURSE_API}/courses`);
        const courses = data.data ?? data;
        const count = data.count ?? courses.length;

        serviceStatus.courses = true;
        setOnline(courseStatus);
        showFormBox(courseFormBox);
        courseCount.textContent = count;

        if (courses.length === 0) {
            coursesTbody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-gray-400 italic">No courses yet.</td></tr>';
            return;
        }

        coursesTbody.innerHTML = courses.map(c => `
            <tr class="hover:bg-slate-50 transition-colors">
                <td class="px-4 py-3 font-medium text-gray-900">${esc(c.name)}</td>
                <td class="px-4 py-3 text-gray-600 max-w-xs truncate">${esc(c.description)}</td>
                <td class="px-4 py-3 text-gray-600">
                    <span class="inline-flex items-center bg-emerald-50 text-emerald-700 text-xs font-semibold px-2 py-0.5 rounded">${c.credits} cr</span>
                </td>
                <td class="px-4 py-3 text-right">
                    <button onclick="window.deleteCourse(${c.id})"
                        class="inline-flex items-center gap-1 text-red-600 hover:text-red-800 hover:bg-red-50 px-2 py-1 rounded text-sm font-medium transition-colors">
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
        showUnavailable(courseTableArea, courseFormBox);
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
    if (!confirm('Delete this course? Related enrollments will also be removed.')) return;

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
    try {
        const { data } = await axios.get(`${ENROLLMENT_API}/enrollments`);
        const enrollments = data.data ?? data;
        const count = data.count ?? enrollments.length;

        serviceStatus.enrollments = true;
        setOnline(enrollmentStatus);
        showFormBox(enrollFormBox);
        enrollmentCount.textContent = count;

        if (enrollments.length === 0) {
            enrollmentsTbody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-gray-400 italic">No enrollments yet.</td></tr>';
            return;
        }

        enrollmentsTbody.innerHTML = enrollments.map(e => `
            <tr class="hover:bg-slate-50 transition-colors">
                <td class="px-4 py-3 font-medium text-gray-900">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-indigo-100 text-indigo-600 text-xs font-bold">
                            ${esc(e.student_name?.charAt(0)?.toUpperCase() || '?')}
                        </span>
                        ${esc(e.student_name || 'Unknown')}
                    </div>
                </td>
                <td class="px-4 py-3 text-gray-600">
                    <span class="inline-flex items-center bg-emerald-50 text-emerald-700 text-xs font-semibold px-2 py-0.5 rounded">
                        ${esc(e.course_name || 'Unknown')}
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-500">${new Date(e.enrolled_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</td>
                <td class="px-4 py-3 text-right">
                    <button onclick="window.deleteEnrollment(${e.id})"
                        class="inline-flex items-center gap-1 text-red-600 hover:text-red-800 hover:bg-red-50 px-2 py-1 rounded text-sm font-medium transition-colors">
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
        showUnavailable(enrollTableArea, enrollFormBox);
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
    if (!confirm('Remove this enrollment?')) return;

    try {
        await axios.delete(`${ENROLLMENT_API}/enrollments/${id}`);
        showToast('Enrollment removed.');
        loadEnrollments();
    } catch (err) {
        const msg = err.response?.data?.message || err.message;
        showToast(msg, 'error');
    }
};

// ========== Initial Load ==========

document.addEventListener('DOMContentLoaded', () => {
    // Load all three sections in parallel — each fails independently
    Promise.allSettled([
        loadStudents(),
        loadCourses(),
        loadEnrollments(),
    ]).then(() => {
        // Dropdowns depend on student + course data, load after
        loadEnrollmentDropdowns();
    });
});
