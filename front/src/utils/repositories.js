export const fetchLocalRepositories = async () => {
    const token = localStorage.getItem('token');
    const res = await fetch('http://localhost:8000/api/repositories', {
        headers: { 'Authorization': `Bearer ${token}` }
    });
    const data = await res.json();
    return data.repositories;
};

export const fetchGitHubRepositories = async () => {
    const token = localStorage.getItem('token');
    const res = await fetch('http://localhost:8000/api/github/repositories', {
        headers: { 'Authorization': `Bearer ${token}` }
    });
    const data = await res.json();
    return data.repositories || [];
};
