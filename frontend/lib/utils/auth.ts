/**
 * Clear all authentication-related data
 */
export function clearAuthData() {
  // Clear auth data
  localStorage.removeItem('auth_token');
  localStorage.removeItem('user');
  localStorage.removeItem('redirect_after_login');
  
  // Clear session storage
  sessionStorage.removeItem('redirect_after_login');
  
  // You can add more items here if needed
  console.log('All auth data cleared');
}