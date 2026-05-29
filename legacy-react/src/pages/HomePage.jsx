import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { supabase } from '../lib/supabaseClient';
import ItemCard from '../components/ItemCard';

function HomePage() {
  const [recentItems, setRecentItems] = useState([]);
  const [selectedItem, setSelectedItem] = useState(null); // Added for Popup state

  useEffect(() => {
    const fetchRecent = async () => {
      if (!supabase) return;
      const { data } = await supabase
        .from('items')
        .select('*')
        .order('created_at', { ascending: false })
        .limit(4);
      setRecentItems(data || []);
    };
    fetchRecent();
  }, []);

  return (
    <div className="bg-white min-vh-100 pb-5">
      {/* --- HERO SECTION --- */}
      <section className="text-center text-white shadow-sm mb-5" style={{ background: '#0d6efd', borderBottom: '5px solid #ffc107' }}>
        <div className="container py-5">
          <h1 className="display-3 fw-bold mb-3 text-uppercase">YOU LOST WE FOUND</h1>
          <p className="lead fw-bold mb-5 mx-auto opacity-100" style={{ maxWidth: '750px' }}>
            The official digital portal for the RUPP community. Report lost items or browse 
            our verified database to find what you've lost.
          </p>
          <div className="d-flex flex-column flex-sm-row justify-content-center gap-3 pb-4">
            <Link to="/board" className="btn btn-light btn-lg px-5 fw-bold rounded-pill border border-2 border-dark shadow">
              Browse Board
            </Link>
            <Link to="/report" className="btn btn-warning btn-lg px-5 fw-bold rounded-pill border border-2 border-dark shadow">
              + Report Item
            </Link>
          </div>
        </div>
      </section>

      {/* --- HOW IT WORKS SECTION --- */}
      <section className="container mb-5">
        <div className="row g-4 text-center justify-content-center">
          {[
            { icon: "🔍", title: "1. Search", text: "Check the community board for your missing item." },
            { icon: "📝", title: "2. Report", text: "Submit a report if you found or lost something." },
            { icon: "🤝", title: "3. Reunited", text: "Connect with the owner and return the item." }
          ].map((step, i) => (
            <div key={i} className="col-md-4 col-sm-10">
              <div className="p-4 bg-light rounded-4 border border-2 border-dark h-100 shadow-sm">
                <div className="display-4 mb-3">{step.icon}</div>
                <h4 className="fw-bold">{step.title}</h4>
                <p className="text-muted mb-0 fw-semibold">{step.text}</p>
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* --- RECENT ACTIVITY SECTION --- */}
      <section className="container mt-5">
        <div className="d-flex justify-content-between align-items-center mb-4 px-2">
          <h2 className="fw-bold m-0 text-dark text-uppercase">Recent Activity</h2>
          <Link to="/board" className="btn btn-outline-primary fw-bold rounded-pill border-2">
            View All →
          </Link>
        </div>

        <div className="row g-3 g-md-4 justify-content-center px-2">
          {recentItems.length > 0 ? (
            recentItems.map(item => (
              <div key={item.id} className="col-6 col-md-3" onClick={() => setSelectedItem(item)} style={{ cursor: 'pointer' }}>
                <ItemCard item={item} />
              </div>
            ))
          ) : (
            <div className="col-12 text-center py-5">
              <p className="text-muted fw-bold">No recent items reported yet.</p>
            </div>
          )}
        </div>
      </section>

      {/* --- POPUP MODAL (Same as BoardPage) --- */}
      {selectedItem && (
        <div className="modal show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.85)', zIndex: 3000 }} onClick={() => setSelectedItem(null)}>
          <div className="modal-dialog modal-dialog-centered" onClick={(e) => e.stopPropagation()}>
            <div className="modal-content border border-3 border-dark shadow-lg" style={{ borderRadius: '25px', overflow: 'hidden' }}>
              <div className="modal-body p-0 text-dark">
                {selectedItem.image_url && (
  <div className="bg-light border-bottom border-2 border-dark" style={{ height: '400px', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
    <img 
      src={selectedItem.image_url} 
      className="w-100 h-100" 
      style={{ objectFit: 'contain' }} 
      alt={selectedItem.title} 
    />
  </div>
)}<div className="p-4">
                  <div className="d-flex justify-content-between align-items-center mb-2">
                    <h2 className="fw-bold m-0">{selectedItem.title}</h2>
                    <span className={`badge px-3 py-2 rounded-pill border border-2 border-dark ${selectedItem.status === 'lost' ? 'bg-danger text-white' : 'bg-success text-white'}`}>
                      {selectedItem.status.toUpperCase()}
                    </span>
                  </div>
                  <p className="text-muted mb-4 fs-6 fw-bold">📅 {new Date(selectedItem.created_at).toLocaleDateString()}</p>
                  <hr className="border-2 border-dark" />
                  <div className="mb-3">
                    <label className="fw-bold text-primary small text-uppercase mb-1 d-block">Location</label>
                    <p className="fs-5 fw-bold">📍 {selectedItem.location}</p>
                  </div>
                  <div className="mb-3">
                    <label className="fw-bold text-primary small text-uppercase mb-1 d-block">Description</label>
                    <p className="bg-light p-3 rounded-3 border border-dark">{selectedItem.description || 'No description provided.'}</p>
                  </div>
                  <div className="bg-primary text-white p-3 rounded-4 shadow-sm border border-2 border-dark text-center">
                    <label className="fw-bold small text-uppercase mb-1 d-block opacity-75">Contact Details</label>
                    <p className="m-0 fs-4 fw-bold">{selectedItem.contact_info}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default HomePage;