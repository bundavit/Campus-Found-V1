import React from 'react';
import { Link, useNavigate } from 'react-router-dom';

const AdminSidebar = () => {
  const navigate = useNavigate();

  const handleLogout = () => {
    localStorage.removeItem('isAdmin'); 
    navigate('/LoginPage');
  };

  return (
    <div className="bg-dark text-white p-3 vh-100 position-fixed shadow d-none d-lg-block" style={{ width: '260px', zIndex: 1100 }}>
      <div className="mb-5 mt-3 text-center">
        <img src="ITE.jpg" alt="Logo" style={{ width: '60px' }} className="mb-2" />
        <h4 className="fw-bold text-white">ADMIN PANEL</h4>
        <hr className="border-secondary" />
      </div>
            
      <div className="nav flex-column gap-2">
        <Link to="/AdminDashboard" className="nav-link text-white p-3 rounded hover-bg-primary text-decoration-none">
          📊 Dashboard Overview
        </Link>
        <Link to="/" className="nav-link text-white p-3 rounded opacity-75 text-decoration-none">
          🌐 View Public Site
        </Link>
      </div>

      <button onClick={handleLogout} className="btn btn-danger w-100 position-absolute bottom-0 start-0 m-0 rounded-0 py-3 fw-bold">
        LOGOUT
      </button>
    </div>
  );
};

export default AdminSidebar;