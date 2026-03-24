async function instructorPostForm(url, payload) {
  const body = new URLSearchParams();
  Object.keys(payload).forEach((key) => {
    body.append(key, payload[key]);
  });

  const response = await fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: body.toString()
  });

  const text = await response.text();
  if (!response.ok) {
    throw new Error(text || 'Request failed');
  }

  try {
    return JSON.parse(text);
  } catch (_error) {
    return text;
  }
}

function setInstructorStatus(html) {
  const status = document.getElementById('statusInstructorLogMsg');
  if (status) {
    status.innerHTML = html;
  }
}

function clearInstructorLoginField() {
  const form = document.getElementById('instructorLoginForm');
  if (form) {
    form.reset();
  }
}

async function checkInstructorLogin() {
  const emailInput = document.getElementById('insLogEmail');
  const passInput = document.getElementById('insLogPass');
  const email = emailInput ? emailInput.value.trim() : '';
  const pass = passInput ? passInput.value : '';

  if (email === '' || pass.trim() === '') {
    setInstructorStatus('<span class="inline-flex rounded-lg bg-red-50 px-3 py-1.5 text-sm font-semibold text-red-600">Vui lòng nhập đầy đủ email và mật khẩu.</span>');
    return;
  }

  try {
    const data = await instructorPostForm('instructor.php', {
      checkLogemail: 'checklogmail',
      insLogEmail: email,
      insLogPass: pass
    });

    if (Number(data) === 0) {
      setInstructorStatus('<span class="inline-flex rounded-lg bg-red-50 px-3 py-1.5 text-sm font-semibold text-red-600">Email hoặc mật khẩu không đúng.</span>');
      return;
    }

    setInstructorStatus('<span class="inline-flex rounded-lg bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-700">Đăng nhập thành công, đang chuyển hướng...</span>');
    clearInstructorLoginField();
    window.setTimeout(() => {
      window.location.href = 'instructorDashboard.php';
    }, 700);
  } catch (_error) {
    setInstructorStatus('<span class="inline-flex rounded-lg bg-red-50 px-3 py-1.5 text-sm font-semibold text-red-600">Không thể kết nối máy chủ.</span>');
  }
}

window.checkInstructorLogin = checkInstructorLogin;
window.clearInstructorLoginField = clearInstructorLoginField;
