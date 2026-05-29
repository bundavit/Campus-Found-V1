import React from 'react';
import { Link } from 'react-router-dom';

function Navbar() {
  return (
    <nav className="navbar navbar-expand-lg navbar-dark sticky-top shadow-sm py-2 px-3" 
         style={{ background: '#0d6efd', borderBottom: '5px solid #ffc107' }}>
      <div className="container-fluid d-flex justify-content-between align-items-center">
        {/* BRAND */}
        <Link className="navbar-brand fw-bold fs-3 text-uppercase" to="/" style={{ letterSpacing: '2px' }}>
          LOST <span style={{ color: '#ffc107' }}>& FOUND</span>
        </Link>
        
        {/* MOBILE TOGGLE */}
        <button 
          className="navbar-toggler border-2 border-dark shadow-none" 
          type="button" 
          data-bs-toggle="collapse" 
          data-bs-target="#navbarNav"
        >
          <span className="navbar-toggler-icon"></span>
        </button>

        {/* LINKS CONTAINER */}
        <div className="collapse navbar-collapse" id="navbarNav">
          {/* w-100 and justify-content-center ensures everything stays in the middle on mobile */}
          <div className="navbar-nav ms-auto align-items-center gap-3 mt-3 mt-lg-0 w-100 justify-content-lg-end justify-content-center">
            
            {/* BOARD - NOW A FRAMED BUTTON */}
            <Link 
              to="/board"
              className="btn btn-outline-light fw-bold px-4 rounded-pill shadow border border-2 border-dark text-uppercase"
              style={{ 
                letterSpacing: '1px',
                fontSize: '0.9rem',
                backgroundColor: 'white',
                color: '#000000'
              }}
            >
              Board
            </Link>
            
            {/* REPORT BUTTON - FRAMED */}
            <Link 
              to="/report" 
              className="btn btn-warning fw-bold px-4 rounded-pill shadow border border-2 border-dark text-uppercase"
              style={{ 
                letterSpacing: '1px',
                fontSize: '0.9rem',
                backgroundColor: '#ffc107',
                color: '#000000'
              }}
            >
              + Report Item
            </Link>
          </div>
        </div>
      </div>
    </nav>
  );
}

export default Navbar;
