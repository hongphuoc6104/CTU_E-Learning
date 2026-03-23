(function () {
  const emailPattern = /^[A-Z0-9._%+-]+@([A-Z0-9-]+\.)+[A-Z]{2,}$/i;

  function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

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

  function setHtml(id, html) {
    const el = document.getElementById(id);
    if (el) {
      el.innerHTML = html;
    }
  }

  function setSignupDisabled(disabled) {
    const button = document.getElementById('signup');
    if (button) {
      button.disabled = disabled;
    }
  }

  function bindEmailValidation() {
    const emailInput = document.getElementById('stuemail');
    if (!emailInput) {
      return;
    }

    let debounceTimer = null;
    const validateEmail = async () => {
      const stuemail = emailInput.value.trim();
      if (stuemail === '') {
        setHtml('statusMsg2', '<small class="text-red-500">Vui lòng nhập Email!</small>');
        setSignupDisabled(false);
        return;
      }

      if (!emailPattern.test(stuemail)) {
        setHtml('statusMsg2', '<small class="text-red-500">Vui lòng nhập email hợp lệ (vd: example@mail.com)</small>');
        setSignupDisabled(false);
        return;
      }

      try {
        const data = await postForm('Student/addstudent.php', {
          checkemail: 'checkmail',
          stuemail: stuemail
        });

        if (Number(data) !== 0) {
          setHtml('statusMsg2', '<small class="text-red-500">Email đã được đăng ký!</small>');
          setSignupDisabled(true);
        } else {
          setHtml('statusMsg2', '<small class="text-emerald-600">Email hợp lệ!</small>');
          setSignupDisabled(false);
        }
      } catch (_error) {
        setHtml('statusMsg2', '<small class="text-red-500">Không thể kiểm tra email lúc này.</small>');
      }
    };

    const debouncedValidate = () => {
      window.clearTimeout(debounceTimer);
      debounceTimer = window.setTimeout(validateEmail, 250);
    };

    emailInput.addEventListener('input', debouncedValidate);
    emailInput.addEventListener('blur', validateEmail);
  }

  function showToast(message, type) {
    const icons = {
      success: 'fa-check-circle text-emerald-400',
      info: 'fa-info-circle text-amber-400',
      error: 'fa-exclamation-circle text-red-400'
    };
    const iconClass = icons[type] || icons.info;

    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'custom-toast';
    toast.innerHTML = '<span><i class="fas ' + iconClass + ' mr-2"></i> ' + message + '</span>';
    container.appendChild(toast);

    window.setTimeout(() => {
      toast.style.transition = 'opacity 0.3s ease';
      toast.style.opacity = '0';
      window.setTimeout(() => {
        toast.remove();
      }, 300);
    }, 3000);
  }

  async function addToCart(courseId) {
    try {
      const response = await postForm('cart_api.php', {
        action: 'add',
        course_id: String(courseId),
        csrf_token: getCsrfToken()
      });

      if (response.status === 'success') {
        showToast('Thêm vào giỏ hàng thành công!', 'success');
        updateCartCount();
      } else if (response.status === 'info') {
        showToast(response.msg || 'Thông tin giỏ hàng đã cập nhật.', 'info');
      } else {
        showToast(response.msg || 'Không thể thêm vào giỏ hàng.', 'error');
      }
    } catch (_error) {
      showToast('Lỗi kết nối. Vui lòng đăng nhập!', 'error');
    }
  }

  async function updateCartCount() {
    try {
      const response = await postForm('cart_api.php', {
        action: 'count',
        csrf_token: getCsrfToken()
      });

      if (response.status === 'success') {
        const cartCountEl = document.getElementById('cartCount');
        if (cartCountEl) {
          cartCountEl.textContent = String(response.count || 0);
        }
      }
    } catch (_error) {
      // Ignore count update failure.
    }
  }

  async function addStu() {
    const nameInput = document.getElementById('stuname');
    const emailInput = document.getElementById('stuemail');
    const passInput = document.getElementById('stupass');

    const stuname = nameInput ? nameInput.value.trim() : '';
    const stuemail = emailInput ? emailInput.value.trim() : '';
    const stupass = passInput ? passInput.value : '';

    if (stuname === '') {
      setHtml('statusMsg1', '<small class="text-red-500">Nhập Họ Tên!</small>');
      return false;
    }
    if (stuemail === '') {
      setHtml('statusMsg2', '<small class="text-red-500">Nhập Email!</small>');
      return false;
    }
    if (stupass.trim() === '') {
      setHtml('statusMsg3', '<small class="text-red-500">Nhập Mật khẩu!</small>');
      return false;
    }

    try {
      const data = await postForm('Student/addstudent.php', {
        stusignup: 'stusignup',
        stuname: stuname,
        stuemail: stuemail,
        stupass: stupass
      });

      if (data === 'OK') {
        setHtml(
          'successMsg',
          '<span class="mt-3 inline-flex rounded-xl bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">Đăng ký thành công! Đang chuyển đến trang đăng nhập...</span>'
        );
        clearStuRegField();
        window.setTimeout(() => {
          window.location.href = 'login.php';
        }, 1200);
      } else {
        setHtml(
          'successMsg',
          '<span class="mt-3 inline-flex rounded-xl bg-red-50 px-4 py-2 text-sm font-semibold text-red-600">Đăng ký thất bại!</span>'
        );
      }
    } catch (_error) {
      setHtml(
        'successMsg',
        '<span class="mt-3 inline-flex rounded-xl bg-red-50 px-4 py-2 text-sm font-semibold text-red-600">Không thể gửi yêu cầu đăng ký.</span>'
      );
    }

    return true;
  }

  async function checkStuLogin() {
    const emailInput = document.getElementById('stuLogEmail');
    const passInput = document.getElementById('stuLogPass');
    const stuLogEmail = emailInput ? emailInput.value.trim() : '';
    const stuLogPass = passInput ? passInput.value : '';

    try {
      const data = await postForm('Student/addstudent.php', {
        checkLogemail: 'checklogmail',
        stuLogEmail: stuLogEmail,
        stuLogPass: stuLogPass
      });

      if (Number(data) === 0) {
        setHtml(
          'statusLogMsg',
          '<small class="inline-flex rounded-lg bg-red-50 px-3 py-1.5 text-sm font-semibold text-red-600">Email hoặc mật khẩu không đúng!</small>'
        );
      } else {
        setHtml(
          'statusLogMsg',
          '<small class="inline-flex rounded-lg bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-700">Đăng nhập thành công, đang chuyển hướng...</small>'
        );
        window.setTimeout(() => {
          window.location.href = 'index.php';
        }, 900);
      }
    } catch (_error) {
      setHtml(
        'statusLogMsg',
        '<small class="inline-flex rounded-lg bg-red-50 px-3 py-1.5 text-sm font-semibold text-red-600">Không thể kết nối tới máy chủ.</small>'
      );
    }
  }

  function clearStuRegField() {
    const form = document.getElementById('stuRegForm');
    if (form) {
      form.reset();
    }
    setHtml('statusMsg1', '');
    setHtml('statusMsg2', '');
    setHtml('statusMsg3', '');
  }

  function clearAllStuReg() {
    setHtml('successMsg', '');
    clearStuRegField();
  }

  function clearStuLoginField() {
    const form = document.getElementById('stuLoginForm');
    if (form) {
      form.reset();
    }
  }

  function clearStuLoginWithStatus() {
    setHtml('statusLogMsg', '');
    clearStuLoginField();
  }

  document.addEventListener('DOMContentLoaded', () => {
    bindEmailValidation();
    if (document.getElementById('cartCount')) {
      updateCartCount();
    }
  });

  window.addToCart = addToCart;
  window.updateCartCount = updateCartCount;
  window.addStu = addStu;
  window.checkStuLogin = checkStuLogin;
  window.clearStuRegField = clearStuRegField;
  window.clearAllStuReg = clearAllStuReg;
  window.clearStuLoginField = clearStuLoginField;
  window.clearStuLoginWithStatus = clearStuLoginWithStatus;
})();
