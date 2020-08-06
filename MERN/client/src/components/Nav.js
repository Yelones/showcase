import React from 'react';
import { Link } from 'react-router-dom';
import { connect } from "react-redux";
import { setUser } from '../redux/user/action';
import { getUsername } from '../redux/user/selector';

function Nav(props) {
    function setActive(e) {
        for(let navItem of document.querySelectorAll('ul.nav li a')) {
            navItem.classList.remove('active');
            
            if (navItem.getAttribute('href') === e.target.getAttribute('href')) {
                navItem.classList.add('active');
            }
        }
    }

    const changeUser = (e) => {
        props.setUser({
            name: e.target.value
        });
    }

    return (
        <div>
            <input onChange={changeUser} className="form-control" value={props.username} />
            <ul className="nav">
                <li>
                    <Link to="/" onClick={setActive} className={window.location.pathname === '/' ? 'active' : ''}>Todos</Link>
                </li>
                <li>
                    <Link to="/lottery" onClick={setActive} className={window.location.pathname === '/lottery' ? 'active' : ''}>Lottery</Link>
                </li>
                <li>
                    <Link to="/comment" onClick={setActive} className={window.location.pathname === '/comment' ? 'active' : ''}>Comments</Link>
                </li>
            </ul>
        </div>
    );
}

// export default Nav;
export default connect(state => ({ username: getUsername(state) }), { setUser })(Nav);