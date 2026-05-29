import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';

function LoginPage() {
  const [password, setPassword] = useState('');
  const navigate = useNavigate();

  const handleLogin = (e) => {
    e.preventDefault();
    if (password === 'RUPPSTAFF') {
      localStorage.setItem('isAdmin', 'true');
      navigate('/AdminDashboard'); // Updated redirect
    } else {
      alert('Access Denied: Invalid Admin Key');
    }
  };

  return (
    <div className="vh-100 d-flex align-items-center justify-content-center" 
         style={{ background: 'linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%)' }}>
      
      <div className="card shadow-lg p-4" style={{ maxWidth: '400px', width: '90%', borderRadius: '20px', border: 'none' }}>
        <div className="text-center mb-4">

          <h3 className="fw-bold text-dark">Admin Login</h3>
          <p className="text-muted small">Enter security password to access management tools</p>
        </div>

        <form onSubmit={handleLogin}>
          <div className="mb-4">
            <label className="form-label small fw-bold text-uppercase text-secondary">Admin Password</label>
            <input 
              type="password" 
              className="form-control form-control-lg bg-light border-0" 
              placeholder="••••••••"
              style={{ borderRadius: '10px' }}
              onChange={(e) => setPassword(e.target.value)} 
              required 
            />
          </div>
          <button type="submit" className="btn btn-dark btn-lg w-100 fw-bold shadow" 
                  style={{ borderRadius: '10px', backgroundColor: '#24243e' }}>
            LOGIN TO DASHBOARD
          </button>
        </form>

        <div className="text-center mt-4">
          <a href="/" className="text-decoration-none text-muted small">← Back to Homepage</a>
        </div>
      </div>
    </div>
  );
}

export default LoginPage;