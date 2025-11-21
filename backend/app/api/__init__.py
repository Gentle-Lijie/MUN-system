"""API blueprint and resource registration."""

from flask import Blueprint
from flask_restful import Api

from .auth import (AuthLoginResource, AuthLogoutResource, AuthPasswordResource,
                   AuthProfileResource)
from .delegates import DelegateListResource, DelegateResource
from .health import HealthResource

api_bp = Blueprint('api', __name__, url_prefix='/api')
api = Api(api_bp)

api.add_resource(HealthResource, '/health/ping', endpoint='health_ping')
api.add_resource(AuthLoginResource, '/auth/login', endpoint='auth_login')
api.add_resource(AuthLogoutResource, '/auth/logout', endpoint='auth_logout')
api.add_resource(AuthProfileResource, '/auth/profile', endpoint='auth_profile')
api.add_resource(AuthPasswordResource, '/auth/password',
                 endpoint='auth_password')
api.add_resource(DelegateListResource, '/delegates', endpoint='delegates')
api.add_resource(DelegateResource, '/delegates/<int:delegate_id>',
                 endpoint='delegate_detail')

__all__ = ['api_bp']
