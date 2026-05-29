import React, { useEffect, useState } from 'react';
import { supabase } from '../lib/supabaseClient'; 
import { useNavigate } from 'react-router-dom';
import AdminSidebar from '../components/AdminSidebar';// <--- IMPORTED SIDEBAR

function AdminDashboard() {
  const [items, setItems] = useState([]);
  const [sortOrder, setSortOrder] = useState('desc');
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedItem, setSelectedItem] = useState(null);
  const navigate = useNavigate();

  useEffect(() => {
    // If someone tries to sneak in, send them to the NEW login name
    if (localStorage.getItem('isAdmin') !== 'true') {
      navigate('/LoginPage');
    }
    fetchItems();
  }, [navigate]);

  const fetchItems = async () => {
    if (!supabase) return;
    const { data } = await supabase
      .from('items')
      .select('*')
      .order('created_at', { ascending: sortOrder === 'asc' });
    setItems(data || []);
  };

  const handleDelete = async (id) => {
    if (window.confirm('Are you sure you want to delete this report forever?')) {
      const { error } = await supabase.from('items').delete().eq('id', id);
      if (!error) {
        setItems(items.filter(item => item.id !== id));
        setSelectedItem(null);
      } else {
        alert("Error: " + error.message);
      }
    }
  };

  const totalItems = items.length;
  const lostItems = items.filter(i => i.status === 'lost').length;
  const foundItems = items.filter(i => i.status === 'found').length;

  const filteredItems = items.filter(item =>
    item.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
    item.location.toLowerCase().includes(searchTerm.toLowerCase())
  );

  return (
    <div className="d-flex">
      {/* 1. SIDEBAR ON THE LEFT */}
      <AdminSidebar />

      {/* 2. MAIN CONTENT ON THE RIGHT (Shifted by 260px) */}
      <div className="flex-grow-1" style={{ marginLeft: '260px' }}>
        <div className="p-4 bg-light min-vh-100">
          
          {/* --- STATS SECTION --- */}
          <div className="text-center mb-5">
            <h1 className="fw-bold text-dark display-5">Management Dashboard</h1>
            <p className="text-muted">Securely manage and monitor all reported items.</p>
            
            <div className="row g-3 mt-2 px-md-5">
              <div className="col-md-4">
                <div className="card border-0 shadow-sm bg-primary text-white p-3 rounded-4">
                  <small className="text-uppercase opacity-75">Total Reports</small>
                  <h3 className="fw-bold m-0">{totalItems}</h3>
                </div>
              </div>
              <div className="col-md-4">
                <div className="card border-0 shadow-sm bg-danger text-white p-3 rounded-4">
                  <small className="text-uppercase opacity-75">Lost Items</small>
                  <h3 className="fw-bold m-0">{lostItems}</h3>
                </div>
              </div>
              <div className="col-md-4">
                <div className="card border-0 shadow-sm bg-success text-white p-3 rounded-4">
                  <small className="text-uppercase opacity-75">Found Items</small>
                  <h3 className="fw-bold m-0">{foundItems}</h3>
                </div>
              </div>
            </div>
          </div>

          {/* --- CONTROLS --- */}
          <div className="card border-0 shadow-sm mb-4 rounded-4">
            <div className="card-body d-flex flex-wrap gap-3 align-items-center justify-content-between">
              <input 
                type="text" 
                className="form-control w-auto flex-grow-1 border-0 bg-light py-2" 
                placeholder="Search by title or location..."
                onChange={(e) => setSearchTerm(e.target.value)}
              />
              <select className="form-select w-auto border-0 bg-light py-2" onChange={(e) => setSortOrder(e.target.value)}>
                <option value="desc">Newest First</option>
                <option value="asc">Oldest First</option>
              </select>
            </div>
          </div>

          {/* --- TABLE --- */}
          <div className="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div className="table-responsive">
              <table className="table table-hover align-middle mb-0">
                <thead className="table-dark">
                  <tr>
                    <th className="ps-4 py-3">Item Details</th>
                    <th>Status</th>
                    <th>Date Reported</th>
                    <th className="text-end pe-4">Action</th>
                  </tr>
                </thead>
                <tbody>
                  {filteredItems.map(item => (
                    <tr key={item.id} style={{ cursor: 'pointer' }} onClick={() => setSelectedItem(item)}>
                      <td className="ps-4">
                        <div className="fw-bold text-primary text-uppercase">{item.title}</div>
                        <small className="text-muted">📍 {item.location}</small>
                      </td>
                      <td>
                        <span className={`badge rounded-pill px-3 py-2 ${item.status === 'lost' ? 'bg-danger' : 'bg-success'}`}>
                          {item.status.toUpperCase()}
                        </span>
                      </td>
                      <td className="text-muted small">{new Date(item.created_at).toLocaleDateString()}</td>
                      <td className="text-end pe-4">
                        <button 
                          onClick={(e) => { e.stopPropagation(); handleDelete(item.id); }} 
                          className="btn btn-sm btn-outline-danger px-3 rounded-pill fw-bold"
                        >
                          Delete
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>

          {selectedItem && (
  <div className="modal-overlay" style={overlayStyle} onClick={() => setSelectedItem(null)}>
    <div className="card shadow-lg border border-3 border-dark overflow-hidden" style={modalStyle} onClick={e => e.stopPropagation()}>
      {/* Close Button */}
      <button onClick={() => setSelectedItem(null)} className="btn-close position-absolute top-0 end-0 m-3 bg-white p-2 rounded-circle shadow-sm border border-2 border-dark" style={{ zIndex: 10 }}></button>
      
      {/* Image with 'contain' so it fits perfectly */}
      <div className="bg-dark d-flex align-items-center justify-content-center" style={{ height: '300px' }}>
        <img 
          src={selectedItem.image_url || 'https://via.placeholder.com/400x250?text=No+Image'} 
          className="h-100 mw-100" 
          style={{ objectFit: 'contain' }} 
          alt="Item" 
        />
      </div>

      <div className="p-4 text-dark">
        <div className="d-flex justify-content-between align-items-center mb-2">
          <h2 className="fw-bold m-0 text-uppercase" style={{ fontSize: '1.5rem' }}>{selectedItem.title}</h2>
          <span className={`badge px-3 py-2 rounded-pill border border-2 border-dark ${selectedItem.status === 'lost' ? 'bg-danger text-white' : 'bg-success text-white'}`}>
            {selectedItem.status.toUpperCase()}
          </span>
        </div>
        
        <p className="text-muted mb-4 fs-6 fw-bold">📅 {new Date(selectedItem.created_at).toLocaleDateString()}</p>
        
        <hr className="border-2 border-dark" />
        
        {/* Blue Labels and Location */}
        <div className="mb-3">
          <label className="fw-bold text-primary small text-uppercase mb-1 d-block">Location</label>
          <p className="fs-5 fw-bold">📍 {selectedItem.location}</p>
        </div>

        {/* Description Box */}
        <div className="mb-3">
          <label className="fw-bold text-primary small text-uppercase mb-1 d-block">Description</label>
          <p className="bg-light p-3 rounded-3 border border-2 border-dark fw-semibold">
            {selectedItem.description || 'No description provided.'}
          </p>
        </div>

        {/* Contact Details Styled like Homepage */}
        <div className="bg-primary text-white p-3 rounded-4 shadow-sm border border-2 border-dark text-center mb-3">
          <label className="fw-bold small text-uppercase mb-1 d-block opacity-75">Contact Details</label>
          <p className="m-0 fs-4 fw-bold">{selectedItem.contact_info || 'No contact provided'}</p>
        </div>

        {/* Admin Action Button */}
        <button onClick={() => handleDelete(selectedItem.id)} className="btn btn-danger w-100 fw-bold py-2 rounded-pill border border-2 border-dark shadow-sm">
          DELETE
        </button>
      </div>
    </div>
  </div>
)}
        </div>
      </div>
    </div>
  );
}

const overlayStyle = { position: 'fixed', top: 0, left: 0, width: '100%', height: '100%', backgroundColor: 'rgba(0,0,0,0.7)', display: 'flex', justifyContent: 'center', alignItems: 'center', zIndex: 1200, backdropFilter: 'blur(6px)' };
const modalStyle = { width: '90%', maxWidth: '480px', borderRadius: '28px', backgroundColor: 'white' };

export default AdminDashboard;