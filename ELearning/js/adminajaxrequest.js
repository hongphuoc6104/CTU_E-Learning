async function postForm(url, payload) {
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

function resolveAdminLoginEndpoint() {
  if (typeof window.adminLoginEndpoint === 'string' && window.adminLoginEndpoint.trim() !== '') {
    return window.adminLoginEndpoint;
  }
  return window.location.pathname.includes('/Admin/') ? 'admin.php' : 'Admin/admin.php';
}

function resolveAdminLoginSuccessRedirect() {
  if (typeof window.adminLoginSuccessRedirect === 'string' && window.adminLoginSuccessRedirect.trim() !== '') {
    return window.adminLoginSuccessRedirect;
  }
  return window.location.pathname.includes('/Admin/') ? 'adminDashboard.php' : 'Admin/adminDashboard.php';
}

function setAdminStatus(html) {
  const status = document.getElementById('statusAdminLogMsg');
  if (status) {
    status.innerHTML = html;
  }
}

async function checkAdminLogin() {
  const emailInput = document.getElementById('adminLogEmail');
  const passInput = document.getElementById('adminLogPass');
  const adminLogEmail = emailInput ? emailInput.value.trim() : '';
  const adminLogPass = passInput ? passInput.value : '';

  if (adminLogEmail === '' || adminLogPass.trim() === '') {
    setAdminStatus('<span class="inline-flex rounded-lg bg-red-50 px-3 py-1.5 text-sm font-semibold text-red-600">Vui lòng nhập đầy đủ thông tin đăng nhập.</span>');
    return;
  }

  try {
    const data = await postForm(resolveAdminLoginEndpoint(), {
      checkLogemail: 'checklogmail',
      adminLogEmail: adminLogEmail,
      adminLogPass: adminLogPass
    });

    if (Number(data) === 0) {
      setAdminStatus('<span class="inline-flex rounded-lg bg-red-50 px-3 py-1.5 text-sm font-semibold text-red-600">Email hoặc mật khẩu không đúng!</span>');
      return;
    }

    setAdminStatus('<span class="inline-flex rounded-lg bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-700">Đăng nhập thành công!</span>');
    clearAdminLoginField();
    window.setTimeout(() => {
      window.location.href = resolveAdminLoginSuccessRedirect();
    }, 700);
  } catch (_error) {
    setAdminStatus('<span class="inline-flex rounded-lg bg-red-50 px-3 py-1.5 text-sm font-semibold text-red-600">Không thể kết nối tới máy chủ.</span>');
  }
}

function clearAdminLoginField() {
  const form = document.getElementById('adminLoginForm');
  if (form) {
    form.reset();
  }
}

function clearAdminLoginWithStatus() {
  setAdminStatus('');
  clearAdminLoginField();
}

window.checkAdminLogin = checkAdminLogin;
window.clearAdminLoginField = clearAdminLoginField;
window.clearAdminLoginWithStatus = clearAdminLoginWithStatus;
