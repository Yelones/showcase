import React from 'react';
import { BrowserRouter as Router, Route, Switch } from 'react-router-dom';
import { Provider } from 'react-redux';
import './sass/app.scss';
import Nav from './components/Nav';
import List from './components/List';
import Lottery from './components/Lottery';
import Comment from './components/Comment';
import store from './redux/user/store';

function App() {
  return (
    <div className="App">
      <Provider store={store}>
        <Router>
            <Nav />

            <Switch>
              <Route name="todos" exact path="/">
                <List />
              </Route>

              <Route name="lottery" path="/lottery">
                <Lottery />
              </Route>

              <Route name="comment" path="/comment">
                <Comment />
              </Route>
            </Switch>
        </Router>
      </Provider>
    </div>
  );
}

export default App;
