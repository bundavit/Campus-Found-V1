import React from 'react';

function ItemCard({ item }) {
  const isResolved = item.status === 'resolved';
  const statusColor = item.status === 'lost' ? 'bg-danger' : isResolved ? 'bg-secondary' : 'bg-success';

  return (
    <div className="card h-100 border border-2 border-dark shadow-sm overflow-hidden" style={{ borderRadius: '15px' }}>
      
      {/* CHECKMARK OVERLAY FOR RESOLVED ITEMS */}
      {isResolved && (
        <div className="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" 
             style={{ backgroundColor: 'rgba(255,255,255,0.6)', zIndex: 2 }}>
          <div className="bg-success text-white rounded-circle d-flex align-items-center justify-content-center shadow-lg" 
               style={{ width: '80px', height: '80px', fontSize: '2.5rem', border: '5px solid white' }}>
            ✅
          </div>
        </div>
      )}

      {/* Item Image */}
      <img 
        src={item.image_url || 'https://via.placeholder.com/300'} 
        style={{ height: '240px', objectFit: 'cover' }} 
        alt={item.title} 
      />

      <div className="card-body p-4">
        <div className="d-flex justify-content-between align-items-center mb-3">
          <span className={`badge px-3 py-2 rounded-pill ${statusColor}`}>
            {isResolved ? 'RESOLVED' : item.status.toUpperCase()}
          </span>
          <small className="text-muted fw-bold">
            {new Date(item.created_at).toLocaleDateString()}
          </small>
        </div>
        
        <h4 className={`fw-bold mb-2 ${isResolved ? 'text-muted' : ''}`}>{item.title}</h4>
        <p className="text-muted mb-0">📍 {item.location}</p>
        
        {/* Contact info hidden in card to keep it clean, shown in popup */}
      </div>
    </div>
  );
}

export default ItemCard;